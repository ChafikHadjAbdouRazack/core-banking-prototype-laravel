<?php

declare(strict_types=1);

namespace App\Domain\Basket\Services;

use App\Models\Account;
use App\Models\BasketAsset;
use App\Models\AccountBalance;
use App\Domain\Basket\Events\BasketDecomposed;
use App\Domain\Account\Events\AssetBalanceAdded;
use App\Domain\Account\Events\AssetBalanceSubtracted;
use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Wallet\Services\WalletService;
use App\Domain\Basket\Workflows\ComposeBasketWorkflow;
use App\Domain\Basket\Workflows\DecomposeBasketWorkflow;
use Workflow\WorkflowStub;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BasketAccountService
{
    public function __construct(
        private readonly BasketValueCalculationService $valueCalculationService,
        private readonly WalletService $walletService
    ) {}

    /**
     * Add basket asset balance to an account using proper domain workflow.
     */
    public function addBasketBalance(Account $account, string $basketCode, int $amount): AccountBalance
    {
        // Use WalletService which follows proper domain patterns
        $accountUuid = AccountUuid::fromString($account->uuid);
        $this->walletService->deposit($accountUuid, $basketCode, $amount);
        
        // Return updated balance
        return $account->balances()->where('asset_code', $basketCode)->firstOrFail();
    }

    /**
     * Subtract basket asset balance from an account using proper domain workflow.
     */
    public function subtractBasketBalance(Account $account, string $basketCode, int $amount): AccountBalance
    {
        // Use WalletService which follows proper domain patterns
        $accountUuid = AccountUuid::fromString($account->uuid);
        $this->walletService->withdraw($accountUuid, $basketCode, $amount);
        
        // Return updated balance
        return $account->balances()->where('asset_code', $basketCode)->firstOrFail();
    }

    /**
     * Decompose a basket into its component assets using proper domain workflow.
     */
    public function decomposeBasket(Account $account, string $basketCode, int $amount): array
    {
        // Use workflow for proper domain pattern
        $workflow = WorkflowStub::make(DecomposeBasketWorkflow::class);
        
        return $workflow->start([
            'account_uuid' => (string) $account->uuid,
            'basket_code' => $basketCode,
            'amount' => $amount
        ]);
    }

    /**
     * Compose individual assets into a basket using proper domain workflow.
     */
    public function composeBasket(Account $account, string $basketCode, int $amount): array
    {
        // Use workflow for proper domain pattern
        $workflow = WorkflowStub::make(ComposeBasketWorkflow::class);
        
        return $workflow->start([
            'account_uuid' => (string) $account->uuid,
            'basket_code' => $basketCode,
            'amount' => $amount
        ]);
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
        // Get balances for assets that have corresponding basket assets
        $basketCodes = BasketAsset::pluck('code');
        $basketBalances = $account->balances()
            ->whereIn('asset_code', $basketCodes)
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
            'account_uuid' => (string) $account->uuid,
            'basket_holdings' => $holdings,
            'total_value' => $totalValue,
            'currency' => 'USD',
            'calculated_at' => now()->toISOString(),
        ];
    }

    /**
     * Calculate the required component amounts for a given basket amount.
     * Used for planning basket compositions.
     */
    public function calculateRequiredComponents(string $basketCode, int $amount): array
    {
        $basket = BasketAsset::where('code', $basketCode)->first();
        
        if (!$basket) {
            throw new \Exception("Basket not found: {$basketCode}");
        }
        $components = $basket->activeComponents;
        $required = [];

        foreach ($components as $component) {
            $requiredAmount = (int) round($amount * ($component->weight / 100));
            $required[$component->asset_code] = $requiredAmount;
        }

        return $required;
    }
}