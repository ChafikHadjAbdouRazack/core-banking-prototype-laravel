<?php

declare(strict_types=1);

namespace App\Domain\Basket\Services;

use App\Models\BasketAsset;
use App\Models\BasketValue;
use App\Domain\Basket\Events\BasketRebalanced;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\EventSourcing\Facades\Projectionist;

class BasketRebalancingService
{
    public function __construct(
        private readonly BasketValueCalculationService $valueCalculationService
    ) {}

    /**
     * Check if a basket needs rebalancing and perform it if necessary.
     */
    public function rebalanceIfNeeded(BasketAsset $basket): ?array
    {
        if (!$basket->needsRebalancing()) {
            return null;
        }

        return $this->rebalance($basket);
    }

    /**
     * Force rebalancing of a basket.
     */
    public function rebalance(BasketAsset $basket): array
    {
        if ($basket->type !== 'dynamic') {
            throw new \Exception('Only dynamic baskets can be rebalanced');
        }

        // Calculate current value to get component weights
        $currentValue = $this->valueCalculationService->calculateValue($basket);
        
        if ($currentValue->value <= 0) {
            throw new \Exception('Cannot rebalance basket with zero or negative value');
        }

        $adjustments = $this->calculateAdjustments($basket, $currentValue);

        if (empty($adjustments)) {
            Log::info("Basket {$basket->code} does not need rebalancing");
            return [
                'status' => 'no_changes_needed',
                'basket' => $basket->code,
                'checked_at' => now()->toISOString(),
            ];
        }

        // Perform rebalancing
        $result = $this->executeRebalancing($basket, $adjustments);

        return $result;
    }

    /**
     * Calculate required adjustments for rebalancing.
     */
    private function calculateAdjustments(BasketAsset $basket, BasketValue $currentValue): array
    {
        $adjustments = [];
        $components = $basket->activeComponents;

        foreach ($components as $component) {
            $currentWeight = $currentValue->getActualWeight($component->asset_code) ?? 0;
            $targetWeight = $component->weight;

            // Check if component is outside allowed bounds
            $needsAdjustment = false;
            $adjustmentTarget = $targetWeight;

            if ($component->min_weight !== null && $currentWeight < $component->min_weight) {
                $needsAdjustment = true;
                $adjustmentTarget = max($component->min_weight, $targetWeight);
            } elseif ($component->max_weight !== null && $currentWeight > $component->max_weight) {
                $needsAdjustment = true;
                $adjustmentTarget = min($component->max_weight, $targetWeight);
            } elseif (abs($currentWeight - $targetWeight) > 1.0) { // 1% tolerance
                $needsAdjustment = true;
            }

            if ($needsAdjustment) {
                $adjustments[] = [
                    'asset' => $component->asset_code,
                    'current_weight' => round($currentWeight, 2),
                    'target_weight' => round($adjustmentTarget, 2),
                    'adjustment' => round($adjustmentTarget - $currentWeight, 2),
                    'action' => $adjustmentTarget > $currentWeight ? 'increase' : 'decrease',
                ];
            }
        }

        return $adjustments;
    }

    /**
     * Execute the rebalancing adjustments.
     */
    private function executeRebalancing(BasketAsset $basket, array $adjustments): array
    {
        return DB::transaction(function () use ($basket, $adjustments) {
            // Record the rebalancing event
            event(new BasketRebalanced(
                basketCode: $basket->code,
                adjustments: $adjustments,
                rebalancedAt: now()
            ));

            // Update the basket's last rebalanced timestamp
            $basket->update(['last_rebalanced_at' => now()]);

            // Invalidate cached values
            $this->valueCalculationService->invalidateCache($basket);

            // Log the rebalancing
            Log::info("Basket {$basket->code} rebalanced", [
                'adjustments' => $adjustments,
                'timestamp' => now()->toISOString(),
            ]);

            return [
                'status' => 'rebalanced',
                'basket' => $basket->code,
                'adjustments' => $adjustments,
                'rebalanced_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Rebalance all baskets that need it.
     */
    public function rebalanceAll(): array
    {
        $baskets = BasketAsset::needsRebalancing()->get();
        $results = [
            'rebalanced' => [],
            'no_changes' => [],
            'failed' => [],
        ];

        foreach ($baskets as $basket) {
            try {
                $result = $this->rebalance($basket);
                
                if ($result['status'] === 'rebalanced') {
                    $results['rebalanced'][] = $result;
                } else {
                    $results['no_changes'][] = $result;
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'basket' => $basket->code,
                    'error' => $e->getMessage(),
                ];
                
                Log::error("Failed to rebalance basket {$basket->code}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Simulate rebalancing without executing it.
     */
    public function simulateRebalancing(BasketAsset $basket): array
    {
        if ($basket->type !== 'dynamic') {
            throw new \Exception('Only dynamic baskets can be rebalanced');
        }

        $currentValue = $this->valueCalculationService->calculateValue($basket);
        $adjustments = $this->calculateAdjustments($basket, $currentValue);

        return [
            'basket' => $basket->code,
            'current_value' => $currentValue->value,
            'adjustments' => $adjustments,
            'needs_rebalancing' => !empty($adjustments),
            'simulated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get rebalancing history for a basket.
     */
    public function getRebalancingHistory(BasketAsset $basket, int $limit = 10): array
    {
        // This would query the event store for BasketRebalanced events
        // For now, return a placeholder
        return [
            'basket' => $basket->code,
            'history' => [],
            'message' => 'Rebalancing history will be available after event store integration',
        ];
    }
}