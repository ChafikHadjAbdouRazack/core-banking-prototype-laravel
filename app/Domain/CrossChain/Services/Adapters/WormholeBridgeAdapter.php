<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Services\Adapters;

use App\Domain\CrossChain\Contracts\BridgeAdapterInterface;
use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\ValueObjects\BridgeQuote;
use App\Domain\CrossChain\ValueObjects\BridgeRoute;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Wormhole V2 Portal Token Bridge adapter.
 *
 * In production, integrates with Wormhole Guardian network for cross-chain token transfers.
 * Currently uses demo-mode simulation with production-ready interface.
 */
class WormholeBridgeAdapter implements BridgeAdapterInterface
{
    private const SUPPORTED_CHAINS = [
        'ethereum', 'polygon', 'bsc', 'arbitrum', 'optimism', 'base', 'solana',
    ];

    public function getProvider(): BridgeProvider
    {
        return BridgeProvider::WORMHOLE;
    }

    public function estimateFee(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): array {
        $baseFee = $this->getBaseFee($sourceChain, $destChain);
        $relayerFee = $this->getRelayerFee($amount);
        $totalFee = bcadd($baseFee, $relayerFee, 8);

        return [
            'fee'            => $totalFee,
            'fee_currency'   => 'USD',
            'estimated_time' => BridgeProvider::WORMHOLE->getAverageTransferTime(),
        ];
    }

    public function getQuote(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): BridgeQuote {
        $feeData = $this->estimateFee($sourceChain, $destChain, $token, $amount);
        $outputAmount = bcsub($amount, $feeData['fee'], 8);

        $route = new BridgeRoute(
            $sourceChain,
            $destChain,
            $token,
            BridgeProvider::WORMHOLE,
            $feeData['estimated_time'],
            $feeData['fee'],
        );

        return new BridgeQuote(
            quoteId: 'wormhole-' . Str::uuid()->toString(),
            route: $route,
            inputAmount: $amount,
            outputAmount: $outputAmount,
            fee: $feeData['fee'],
            feeCurrency: $feeData['fee_currency'],
            estimatedTimeSeconds: $feeData['estimated_time'],
            expiresAt: CarbonImmutable::now()->addSeconds((int) config('crosschain.fees.quote_ttl_seconds', 300)),
        );
    }

    public function initiateBridge(
        BridgeQuote $quote,
        string $senderAddress,
        string $recipientAddress,
    ): array {
        Log::info('Wormhole: Initiating bridge transfer', [
            'source'    => $quote->getSourceChain()->value,
            'dest'      => $quote->getDestChain()->value,
            'token'     => $quote->route->token,
            'amount'    => $quote->inputAmount,
            'sender'    => $senderAddress,
            'recipient' => $recipientAddress,
        ]);

        // In production: submit VAA (Verified Action Approval) to token bridge
        $transactionId = 'wormhole-tx-' . Str::uuid()->toString();

        return [
            'transaction_id' => $transactionId,
            'status'         => BridgeStatus::INITIATED,
            'source_tx_hash' => '0x' . Str::random(64),
        ];
    }

    public function getBridgeStatus(string $transactionId): array
    {
        // In production: query Wormhole Guardian RPC for VAA status
        Log::debug('Wormhole: Checking bridge status', ['transaction_id' => $transactionId]);

        return [
            'status'         => BridgeStatus::COMPLETED,
            'source_tx_hash' => '0x' . md5($transactionId . 'source'),
            'dest_tx_hash'   => '0x' . md5($transactionId . 'dest'),
            'confirmations'  => 15,
        ];
    }

    public function getSupportedRoutes(): array
    {
        $chains = array_map(
            fn (string $chain) => CrossChainNetwork::from($chain),
            self::SUPPORTED_CHAINS,
        );

        $routes = [];
        $tokens = ['USDC', 'USDT', 'WETH', 'WBTC'];

        foreach ($chains as $source) {
            foreach ($chains as $dest) {
                if ($source === $dest) {
                    continue;
                }
                foreach ($tokens as $token) {
                    $routes[] = new BridgeRoute(
                        $source,
                        $dest,
                        $token,
                        BridgeProvider::WORMHOLE,
                        BridgeProvider::WORMHOLE->getAverageTransferTime(),
                        $this->getBaseFee($source, $dest),
                    );
                }
            }
        }

        return $routes;
    }

    public function supportsRoute(CrossChainNetwork $source, CrossChainNetwork $dest, string $token): bool
    {
        return in_array($source->value, self::SUPPORTED_CHAINS)
            && in_array($dest->value, self::SUPPORTED_CHAINS)
            && $source !== $dest;
    }

    private function getBaseFee(CrossChainNetwork $source, CrossChainNetwork $dest): string
    {
        // Wormhole fees vary by chain pair
        $isL2 = in_array($source->value, ['arbitrum', 'optimism', 'base'])
            || in_array($dest->value, ['arbitrum', 'optimism', 'base']);

        return $isL2 ? '0.50' : '2.00';
    }

    private function getRelayerFee(string $amount): string
    {
        // 0.05% relayer fee
        return bcmul($amount, '0.0005', 8);
    }
}
