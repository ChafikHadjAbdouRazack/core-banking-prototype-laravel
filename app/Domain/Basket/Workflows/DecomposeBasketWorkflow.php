<?php

namespace App\Domain\Basket\Workflows;

use Workflow\Activity;
use Workflow\Workflow;

class DecomposeBasketWorkflow extends Workflow
{
    public function execute(array $input): array
    {
        $accountUuid = $input['account_uuid'];
        $basketCode = $input['basket_code'];
        $amount = $input['amount'];

        // Step 1: Validate basket decomposition
        $validationResult = yield Activity::run(
            'App\Domain\Basket\Activities\ValidateBasketDecompositionActivity',
            [
                'basket_code' => $basketCode,
                'amount' => $amount
            ]
        );

        if (!$validationResult['valid']) {
            throw new \Exception($validationResult['error']);
        }

        // Step 2: Validate basket balance
        $balanceValidation = yield Activity::run(
            'App\Domain\Basket\Activities\ValidateBasketBalanceActivity',
            [
                'account_uuid' => $accountUuid,
                'basket_code' => $basketCode,
                'amount' => $amount
            ]
        );

        if (!$balanceValidation['sufficient']) {
            throw new \Exception($balanceValidation['error']);
        }

        // Step 3: Calculate component amounts
        $componentAmounts = yield Activity::run(
            'App\Domain\Basket\Activities\CalculateComponentAmountsActivity',
            [
                'basket_code' => $basketCode,
                'amount' => $amount
            ]
        );

        // Step 4: Withdraw basket asset
        yield Activity::run(
            'App\Domain\Basket\Activities\WithdrawBasketAssetActivity',
            [
                'account_uuid' => $accountUuid,
                'basket_code' => $basketCode,
                'amount' => $amount
            ]
        );

        // Step 5: Deposit component assets
        yield Activity::run(
            'App\Domain\Basket\Activities\DepositComponentAssetsActivity',
            [
                'account_uuid' => $accountUuid,
                'component_amounts' => $componentAmounts['amounts']
            ]
        );

        // Step 6: Record decomposition event
        yield Activity::run(
            'App\Domain\Basket\Activities\RecordBasketDecompositionActivity',
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
            'components' => $componentAmounts['amounts'],
            'decomposed_at' => now()->toISOString()
        ];
    }

    public function compensate(array $input): void
    {
        $accountUuid = $input['account_uuid'];
        $basketCode = $input['basket_code'];
        $amount = $input['amount'];

        // Compensate by withdrawing components and depositing basket back
        yield Activity::run(
            'App\Domain\Basket\Activities\WithdrawComponentAssetsActivity',
            [
                'account_uuid' => $accountUuid,
                'component_amounts' => $input['component_amounts']
            ]
        );

        yield Activity::run(
            'App\Domain\Basket\Activities\DepositBasketAssetActivity',
            [
                'account_uuid' => $accountUuid,
                'basket_code' => $basketCode,
                'amount' => $amount
            ]
        );
    }
}