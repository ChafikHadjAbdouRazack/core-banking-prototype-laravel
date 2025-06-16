<?php

declare(strict_types=1);

namespace App\Domain\Basket\Services;

use App\Models\Account;
use App\Models\BasketAsset;
use App\Models\AccountBalance;
use App\Domain\Basket\Events\BasketDecomposed;
use App\Domain\Account\Events\AssetBalanceAdded;
use App\Domain\Account\Events\AssetBalanceSubtracted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BasketAccountService
{
    public function __construct(
        private readonly BasketValueCalculationService $valueCalculationService
    ) {}

    /**
     * Add basket asset balance to an account.
     */
    public function addBasketBalance(Account $account, string $basketCode, int $amount): AccountBalance
    {
        $basket = BasketAsset::where('code', $basketCode)->firstOrFail();
        
        if (!$basket->is_active) {
            throw new \Exception("Basket {$basketCode} is not active");
        }

        // Ensure the basket exists as an asset
        $basket->toAsset();

        // Add balance like any other asset
        $balance = $account->balances()->firstOrCreate(
            ['asset_code' => $basketCode],
            ['balance' => 0]
        );

        $balance->credit($amount);

        // Record event
        event(new AssetBalanceAdded(
            accountUuid: $account->uuid,
            assetCode: $basketCode,
            amount: $amount,
            metadata: ['type' => 'basket_deposit']
        ));

        Log::info("Added {$amount} of basket {$basketCode} to account {$account->uuid}");

        return $balance;
    }

    /**
     * Subtract basket asset balance from an account.
     */
    public function subtractBasketBalance(Account $account, string $basketCode, int $amount): AccountBalance
    {
        $balance = $account->balances()
            ->where('asset_code', $basketCode)
            ->firstOrFail();

        if ($balance->balance < $amount) {
            throw new \Exception("Insufficient basket balance. Available: {$balance->balance}, Requested: {$amount}");
        }

        $balance->debit($amount);

        // Record event
        event(new AssetBalanceSubtracted(
            accountUuid: $account->uuid,
            assetCode: $basketCode,
            amount: $amount,
            metadata: ['type' => 'basket_withdrawal']
        ));

        Log::info("Subtracted {$amount} of basket {$basketCode} from account {$account->uuid}");

        return $balance;
    }

    /**
     * Decompose a basket into its component assets.
     * This converts basket holdings into individual asset holdings.
     */
    public function decomposeBasket(Account $account, string $basketCode, int $amount): array
    {
        $basket = BasketAsset::where('code', $basketCode)->firstOrFail();
        
        if (!$basket->is_active) {
            throw new \Exception("Basket {$basketCode} is not active");
        }

        // Validate basket weights
        if (!$basket->validateWeights()) {
            throw new \Exception("Basket {$basketCode} has invalid component weights");
        }

        // Check sufficient balance
        $basketBalance = $account->balances()
            ->where('asset_code', $basketCode)
            ->first();

        if (!$basketBalance || $basketBalance->balance < $amount) {
            throw new \Exception("Insufficient basket balance for decomposition");
        }

        return DB::transaction(function () use ($account, $basket, $basketCode, $amount, $basketBalance) {
            // Calculate component amounts based on weights
            $componentAmounts = $this->calculateComponentAmounts($basket, $amount);

            // Subtract basket balance
            $this->subtractBasketBalance($account, $basketCode, $amount);

            // Add component balances
            foreach ($componentAmounts as $assetCode => $componentAmount) {
                $balance = $account->balances()->firstOrCreate(
                    ['asset_code' => $assetCode],
                    ['balance' => 0]
                );

                $balance->credit($componentAmount);

                // Record event for each component
                event(new AssetBalanceAdded(
                    accountUuid: $account->uuid,
                    assetCode: $assetCode,
                    amount: $componentAmount,
                    metadata: [
                        'type' => 'basket_decomposition',
                        'basket_code' => $basketCode,
                        'basket_amount' => $amount,
                    ]
                ));
            }

            // Record decomposition event
            event(new BasketDecomposed(
                accountUuid: $account->uuid,
                basketCode: $basketCode,
                amount: $amount,
                componentAmounts: $componentAmounts,
                decomposedAt: now()
            ));

            Log::info("Decomposed {$amount} of basket {$basketCode} for account {$account->uuid}", [
                'components' => $componentAmounts,
            ]);

            return [
                'basket_code' => $basketCode,
                'basket_amount' => $amount,
                'components' => $componentAmounts,
                'decomposed_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Compose individual assets into a basket.
     * This is the reverse of decomposition.
     */
    public function composeBasket(Account $account, string $basketCode, int $amount): array
    {
        $basket = BasketAsset::where('code', $basketCode)->firstOrFail();
        
        if (!$basket->is_active) {
            throw new \Exception("Basket {$basketCode} is not active");
        }

        // Validate basket weights
        if (!$basket->validateWeights()) {
            throw new \Exception("Basket {$basketCode} has invalid component weights");
        }

        return DB::transaction(function () use ($account, $basket, $basketCode, $amount) {
            // Calculate required component amounts
            $requiredAmounts = $this->calculateComponentAmounts($basket, $amount);

            // Verify account has sufficient component balances
            foreach ($requiredAmounts as $assetCode => $requiredAmount) {
                $balance = $account->balances()
                    ->where('asset_code', $assetCode)
                    ->first();

                if (!$balance || $balance->balance < $requiredAmount) {
                    throw new \Exception("Insufficient {$assetCode} balance. Required: {$requiredAmount}, Available: " . ($balance ? $balance->balance : 0));
                }
            }

            // Subtract component balances
            foreach ($requiredAmounts as $assetCode => $requiredAmount) {
                $balance = $account->balances()
                    ->where('asset_code', $assetCode)
                    ->first();

                $balance->debit($requiredAmount);

                // Record event for each component
                event(new AssetBalanceSubtracted(
                    accountUuid: $account->uuid,
                    assetCode: $assetCode,
                    amount: $requiredAmount,
                    metadata: [
                        'type' => 'basket_composition',
                        'basket_code' => $basketCode,
                        'basket_amount' => $amount,
                    ]
                ));
            }

            // Add basket balance
            $this->addBasketBalance($account, $basketCode, $amount);

            Log::info("Composed {$amount} of basket {$basketCode} for account {$account->uuid}", [
                'components_used' => $requiredAmounts,
            ]);

            return [
                'basket_code' => $basketCode,
                'basket_amount' => $amount,
                'components_used' => $requiredAmounts,
                'composed_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Calculate component amounts based on basket weights.
     */
    private function calculateComponentAmounts(BasketAsset $basket, int $basketAmount): array
    {
        $componentAmounts = [];
        $components = $basket->activeComponents;

        foreach ($components as $component) {
            // Calculate proportional amount based on weight
            $componentAmount = (int) round($basketAmount * ($component->weight / 100));
            $componentAmounts[$component->asset_code] = $componentAmount;
        }

        return $componentAmounts;
    }

    /**
     * Get the current value of basket holdings for an account.
     */
    public function getBasketHoldingsValue(Account $account): array
    {
        $basketBalances = $account->balances()
            ->whereHas('asset', function ($query) {
                $query->where('is_basket', true);
            })
            ->get();

        $holdings = [];
        $totalValue = 0.0;

        foreach ($basketBalances as $balance) {
            if ($balance->balance <= 0) {
                continue;
            }

            $basket = BasketAsset::where('code', $balance->asset_code)->first();
            if (!$basket) {
                continue;
            }

            $basketValue = $this->valueCalculationService->calculateValue($basket);
            $holdingValue = $basketValue->value * $balance->balance;

            $holdings[] = [
                'basket_code' => $balance->asset_code,
                'basket_name' => $basket->name,
                'balance' => $balance->balance,
                'unit_value' => $basketValue->value,
                'total_value' => $holdingValue,
                'last_calculated' => $basketValue->calculated_at->toISOString(),
            ];

            $totalValue += $holdingValue;
        }

        return [
            'account_uuid' => $account->uuid,
            'basket_holdings' => $holdings,
            'total_value' => $totalValue,
            'currency' => 'USD',
            'calculated_at' => now()->toISOString(),
        ];
    }
}