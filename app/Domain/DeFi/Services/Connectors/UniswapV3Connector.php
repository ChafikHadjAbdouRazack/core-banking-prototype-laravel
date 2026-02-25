<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Services\Connectors;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Contracts\SwapProtocolInterface;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\ValueObjects\SwapQuote;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Uniswap V3 connector: exact input/output swaps, multi-hop routing.
 *
 * In production, integrates with Uniswap V3 SwapRouter and Quoter contracts.
 */
class UniswapV3Connector implements SwapProtocolInterface
{
    private const SUPPORTED_CHAINS = ['ethereum', 'polygon', 'arbitrum', 'optimism', 'base'];

    private const FEE_TIERS = [100, 500, 3000, 10000];

    public function getProtocol(): DeFiProtocol
    {
        return DeFiProtocol::UNISWAP_V3;
    }

    public function getQuote(
        CrossChainNetwork $chain,
        string $fromToken,
        string $toToken,
        string $amount,
        float $slippageTolerance = 0.5,
    ): SwapQuote {
        // In production: call Quoter contract for exact output amount
        $bestFeeTier = $this->findBestFeeTier($fromToken, $toToken, $amount);
        $feeRate = $bestFeeTier / 1000000; // Convert basis points
        $fee = bcmul($amount, (string) $feeRate, 8);
        $priceImpact = $this->estimatePriceImpact($amount);
        $impactFee = bcmul($amount, bcdiv($priceImpact, '100', 8), 8);
        $outputAmount = bcsub(bcsub($amount, $fee, 8), $impactFee, 8);
        $gasEstimate = $this->estimateGas($chain);

        return new SwapQuote(
            quoteId: 'uni-v3-' . Str::uuid()->toString(),
            chain: $chain,
            inputToken: $fromToken,
            outputToken: $toToken,
            inputAmount: $amount,
            outputAmount: $outputAmount,
            priceImpact: $priceImpact,
            protocol: DeFiProtocol::UNISWAP_V3,
            gasEstimate: $gasEstimate,
            feeTier: $bestFeeTier,
            expiresAt: CarbonImmutable::now()->addSeconds((int) config('defi.swap.quote_ttl_seconds', 60)),
        );
    }

    public function executeSwap(SwapQuote $quote, string $walletAddress): array
    {
        Log::info('Uniswap V3: Executing swap', [
            'chain'  => $quote->chain->value,
            'pair'   => "{$quote->inputToken}/{$quote->outputToken}",
            'amount' => $quote->inputAmount,
            'wallet' => $walletAddress,
        ]);

        // In production: call SwapRouter.exactInputSingle()
        return [
            'tx_hash'       => '0x' . Str::random(64),
            'input_amount'  => $quote->inputAmount,
            'output_amount' => $quote->outputAmount,
            'price_impact'  => $quote->priceImpact,
        ];
    }

    public function getSupportedPairs(CrossChainNetwork $chain): array
    {
        if (! in_array($chain->value, self::SUPPORTED_CHAINS)) {
            return [];
        }

        $tokens = ['USDC', 'USDT', 'WETH', 'WBTC', 'DAI'];
        $pairs = [];

        foreach ($tokens as $from) {
            foreach ($tokens as $to) {
                if ($from === $to) {
                    continue;
                }
                foreach (self::FEE_TIERS as $feeTier) {
                    $pairs[] = ['from' => $from, 'to' => $to, 'fee_tier' => $feeTier];
                }
            }
        }

        return $pairs;
    }

    private function findBestFeeTier(string $fromToken, string $toToken, string $amount): int
    {
        // Stablecoin pairs use lowest fee tier
        $stables = ['USDC', 'USDT', 'DAI'];
        if (in_array($fromToken, $stables) && in_array($toToken, $stables)) {
            return 100;
        }

        // Major pairs use 500 or 3000
        $majors = ['WETH', 'WBTC'];
        if (in_array($fromToken, $majors) || in_array($toToken, $majors)) {
            return bccomp($amount, '10000', 2) > 0 ? 500 : 3000;
        }

        return 3000;
    }

    private function estimatePriceImpact(string $amount): string
    {
        // Larger amounts have higher price impact
        if (bccomp($amount, '100000', 2) > 0) {
            return '0.50';
        }
        if (bccomp($amount, '10000', 2) > 0) {
            return '0.10';
        }

        return '0.03';
    }

    private function estimateGas(CrossChainNetwork $chain): string
    {
        return match ($chain) {
            CrossChainNetwork::ETHEREUM => '15.00',
            CrossChainNetwork::POLYGON  => '0.05',
            CrossChainNetwork::ARBITRUM => '0.30',
            CrossChainNetwork::OPTIMISM => '0.20',
            CrossChainNetwork::BASE     => '0.10',
            default                     => '5.00',
        };
    }
}
