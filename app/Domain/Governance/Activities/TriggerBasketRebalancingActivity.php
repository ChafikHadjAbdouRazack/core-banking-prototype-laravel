<?php

namespace App\Domain\Governance\Activities;

use App\Models\BasketAsset;
use Workflow\Activity;

class TriggerBasketRebalancingActivity extends Activity
{
    /**
     * Execute trigger basket rebalancing activity.
     */
    public function execute(string $basketCode): void
    {
        $basket = BasketAsset::where('code', $basketCode)->with('components')->first();

        if (! $basket) {
            return;
        }

        // Trigger rebalancing event
        event(
            new \App\Domain\Basket\Events\BasketRebalanced(
                $basketCode,
                $basket->components->map(
                    function ($component) {
                        return [
                            'asset' => $component->asset_code,
                            'old_weight' => $component->weight,
                            'new_weight' => $component->weight,
                            'adjustment' => 0.0,
                        ];
                    }
                )->toArray(),
                now()
            )
        );
    }
}
