<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Services;

use App\Domain\CrossChain\Contracts\BridgeAdapterInterface;
use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Events\BridgeTransactionCompleted;
use App\Domain\CrossChain\Events\BridgeTransactionFailed;
use App\Domain\CrossChain\Events\BridgeTransactionInitiated;
use App\Domain\CrossChain\Exceptions\BridgeTransactionFailedException;
use App\Domain\CrossChain\Exceptions\UnsupportedBridgeRouteException;
use App\Domain\CrossChain\ValueObjects\BridgeQuote;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BridgeOrchestratorService
{
    /** @var array<string, BridgeAdapterInterface> */
    private array $adapters = [];

    /**
     * Register a bridge adapter.
     */
    public function registerAdapter(BridgeAdapterInterface $adapter): void
    {
        $this->adapters[$adapter->getProvider()->value] = $adapter;
    }

    /**
     * Get all registered adapters.
     *
     * @return array<string, BridgeAdapterInterface>
     */
    public function getAdapters(): array
    {
        return $this->adapters;
    }

    /**
     * Get bridge quotes from all adapters that support the route.
     *
     * @return array<BridgeQuote>
     * @throws UnsupportedBridgeRouteException
     */
    public function getQuotes(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): array {
        $quotes = [];

        foreach ($this->adapters as $adapter) {
            if (! $adapter->supportsRoute($sourceChain, $destChain, $token)) {
                continue;
            }

            try {
                $quotes[] = $adapter->getQuote($sourceChain, $destChain, $token, $amount);
            } catch (Throwable $e) {
                Log::warning('Bridge adapter quote failed', [
                    'provider' => $adapter->getProvider()->value,
                    'source'   => $sourceChain->value,
                    'dest'     => $destChain->value,
                    'token'    => $token,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        if (empty($quotes)) {
            throw UnsupportedBridgeRouteException::forRoute($sourceChain, $destChain, $token);
        }

        return $quotes;
    }

    /**
     * Get the best quote (lowest fee) for a bridge transfer.
     *
     * @throws UnsupportedBridgeRouteException
     */
    public function getBestQuote(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): BridgeQuote {
        $quotes = $this->getQuotes($sourceChain, $destChain, $token, $amount);

        usort($quotes, fn (BridgeQuote $a, BridgeQuote $b) => bccomp($a->fee, $b->fee, 18));

        return $quotes[0];
    }

    /**
     * Get the fastest quote for a bridge transfer.
     *
     * @throws UnsupportedBridgeRouteException
     */
    public function getFastestQuote(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): BridgeQuote {
        $quotes = $this->getQuotes($sourceChain, $destChain, $token, $amount);

        usort($quotes, fn (BridgeQuote $a, BridgeQuote $b) => $a->estimatedTimeSeconds <=> $b->estimatedTimeSeconds);

        return $quotes[0];
    }

    /**
     * Initiate a bridge transfer using a specific quote.
     *
     * @return array{transaction_id: string, status: BridgeStatus}
     * @throws BridgeTransactionFailedException
     */
    public function initiateBridge(
        BridgeQuote $quote,
        string $senderAddress,
        string $recipientAddress,
    ): array {
        if ($quote->isExpired()) {
            throw BridgeTransactionFailedException::quoteExpired($quote->quoteId);
        }

        $adapter = $this->getAdapterForProvider($quote->getProvider());

        try {
            $result = $adapter->initiateBridge($quote, $senderAddress, $recipientAddress);

            BridgeTransactionInitiated::dispatch(
                $result['transaction_id'],
                $quote->getSourceChain(),
                $quote->getDestChain(),
                $quote->route->token,
                $quote->inputAmount,
                $quote->getProvider(),
                $senderAddress,
                $recipientAddress,
            );

            Log::info('Bridge transaction initiated', [
                'transaction_id' => $result['transaction_id'],
                'provider'       => $quote->getProvider()->value,
                'source'         => $quote->getSourceChain()->value,
                'dest'           => $quote->getDestChain()->value,
                'token'          => $quote->route->token,
                'amount'         => $quote->inputAmount,
            ]);

            return [
                'transaction_id' => $result['transaction_id'],
                'status'         => $result['status'],
            ];
        } catch (BridgeTransactionFailedException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw BridgeTransactionFailedException::executionFailed(
                'unknown',
                $e->getMessage(),
                $e,
            );
        }
    }

    /**
     * Check the status of a bridge transaction.
     *
     * @return array{status: BridgeStatus, source_tx_hash: ?string, dest_tx_hash: ?string, confirmations: int}
     */
    public function checkStatus(
        string $transactionId,
        BridgeProvider $provider,
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): array {
        $adapter = $this->getAdapterForProvider($provider);
        $result = $adapter->getBridgeStatus($transactionId);

        if ($result['status'] === BridgeStatus::COMPLETED) {
            BridgeTransactionCompleted::dispatch(
                $transactionId,
                $sourceChain,
                $destChain,
                $token,
                $amount,
                $provider,
                $result['source_tx_hash'],
                $result['dest_tx_hash'],
            );
        }

        if ($result['status'] === BridgeStatus::FAILED) {
            BridgeTransactionFailed::dispatch(
                $transactionId,
                $sourceChain,
                $destChain,
                $token,
                $amount,
                $provider,
                'Bridge transaction failed',
            );
        }

        return $result;
    }

    /**
     * Get all supported routes across all adapters.
     *
     * @return array<\App\Domain\CrossChain\ValueObjects\BridgeRoute>
     */
    public function getAllSupportedRoutes(): array
    {
        $routes = [];

        foreach ($this->adapters as $adapter) {
            $routes = array_merge($routes, $adapter->getSupportedRoutes());
        }

        return $routes;
    }

    /**
     * Get supported chains (those with at least one bridge route).
     *
     * @return array<CrossChainNetwork>
     */
    public function getSupportedChains(): array
    {
        $chains = [];

        foreach ($this->getAllSupportedRoutes() as $route) {
            $chains[$route->sourceChain->value] = $route->sourceChain;
            $chains[$route->destChain->value] = $route->destChain;
        }

        return array_values($chains);
    }

    private function getAdapterForProvider(BridgeProvider $provider): BridgeAdapterInterface
    {
        if (! isset($this->adapters[$provider->value])) {
            throw new RuntimeException("No adapter registered for provider: {$provider->value}");
        }

        return $this->adapters[$provider->value];
    }
}
