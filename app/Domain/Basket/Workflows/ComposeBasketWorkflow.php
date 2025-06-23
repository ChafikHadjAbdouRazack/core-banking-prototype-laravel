<?php

namespace App\Domain\Basket\Workflows;

use Workflow\Activity;
use Workflow\Workflow;
use App\Domain\Basket\Activities\ValidateBasketCompositionActivity;
use App\Domain\Basket\Activities\CalculateComponentAmountsActivity;
use App\Domain\Basket\Activities\ValidateComponentBalancesActivity;
use App\Domain\Basket\Activities\WithdrawComponentAssetsActivity;
use App\Domain\Basket\Activities\DepositBasketAssetActivity;
use App\Domain\Basket\Activities\RecordBasketCompositionActivity;

class ComposeBasketWorkflow extends Workflow
{
    public function execute(array $input): array
    {
        $accountUuid = $input['account_uuid'];
        $basketCode = $input['basket_code'];
        $amount = $input['amount'];

        // Step 1: Validate basket composition
        $validationResult = yield Activity::run(
            ValidateBasketCompositionActivity::class,
            [
                'basket_code' => $basketCode,
                'amount' => $amount
            ]
        );

        if (!$validationResult['valid']) {
            throw new \Exception($validationResult['error']);
        }

        // Step 2: Calculate required component amounts
        $componentAmounts = yield Activity::run(
            CalculateComponentAmountsActivity::class,
            [
                'basket_code' => $basketCode,
                'amount' => $amount
            ]
        );

        // Step 3: Validate component balances
        $balanceValidation = yield Activity::run(
            ValidateComponentBalancesActivity::class,
            [
                'account_uuid' => $accountUuid,
                'required_amounts' => $componentAmounts['amounts']
            ]
        );

        if (!$balanceValidation['sufficient']) {
            throw new \Exception($balanceValidation['error']);
        }

        // Step 4: Withdraw component assets
        yield Activity::run(
            WithdrawComponentAssetsActivity::class,
            [
                'account_uuid' => $accountUuid,
                'component_amounts' => $componentAmounts['amounts']
            ]
        );

        // Step 5: Deposit basket asset
        yield Activity::run(
            DepositBasketAssetActivity::class,
            [
                'account_uuid' => $accountUuid,
                'basket_code' => $basketCode,
                'amount' => $amount
            ]
        );

        // Step 6: Record composition event
        $compositionResult = yield Activity::run(
            RecordBasketCompositionActivity::class,
            [
                'account_uuid' => $accountUuid,
                'basket_code' => $basketCode,
                'amount' => $amount,
                'component_amounts' => $componentAmounts['amounts'],
                'exchange_rates' => $componentAmounts['exchange_rates']
            ]
        );

        return [
            'basket_code' => $basketCode,
            'basket_amount' => $amount,
            'components_used' => $componentAmounts['amounts'],
            'composed_at' => now()->toISOString()
        ];
    }

    public function compensate(array $input): void
    {
        $accountUuid = $input['account_uuid'];
        $basketCode = $input['basket_code'];
        $amount = $input['amount'];

        // Compensate by withdrawing basket and depositing components back
        yield Activity::run(
            WithdrawBasketAssetActivity::class,
            [
                'account_uuid' => $accountUuid,
                'basket_code' => $basketCode,
                'amount' => $amount
            ]
        );

        yield Activity::run(
            DepositComponentAssetsActivity::class,
            [
                'account_uuid' => $accountUuid,
                'component_amounts' => $input['component_amounts']
            ]
        );
    }
}