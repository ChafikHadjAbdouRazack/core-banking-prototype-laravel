<?php

namespace App\Domain\Governance\Activities;

use App\Models\BasketAsset;
use Workflow\Activity;

class UpdateBasketComponentsActivity extends Activity
{
    /**
     * Execute update basket components activity.
     *
     * @param  string $basketCode
     * @param  array  $composition
     * @return void
     */
    public function execute(string $basketCode, array $composition): void
    {
        $basket = BasketAsset::where('code', $basketCode)->first();

        if (! $basket) {
            throw new \Exception("Basket {$basketCode} not found");
        }

        foreach ($composition as $assetCode => $weight) {
            $basket->components()
                ->where('asset_code', $assetCode)
                ->update(['weight' => $weight]);
        }

        $basket->update(['last_rebalanced_at' => now()]);
    }
}
