<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use Workflow\Workflow;
use Workflow\ChildWorkflowStub;

class TransferAccountWorkflow extends Workflow
{
    /**
     * Execute a transfer between accounts with compensation handling
     * 
     * @param string $from Source account UUID
     * @param string $to Destination account UUID
     * @param int $amount Amount to transfer
     *
     * @return \Generator
     */
    public function execute(string $from, string $to, int $amount): \Generator
    {
        // Convert inputs to proper types
        $fromAccount = __account_uuid($from);
        $toAccount = __account_uuid($to);
        $money = __money($amount);

        try {
            // Step 1: Withdraw from source account
            yield ChildWorkflowStub::make(
                WithdrawAccountWorkflow::class,
                $fromAccount,
                $money
            );

            // Add compensation in case subsequent steps fail
            $this->addCompensation(
                fn () => ChildWorkflowStub::make(
                    DepositAccountWorkflow::class,
                    $fromAccount,
                    $money
                )
            );

            // Step 2: Deposit to destination account
            yield ChildWorkflowStub::make(
                DepositAccountWorkflow::class,
                $toAccount,
                $money
            );
        } catch (\Throwable $e) {
            // Run all registered compensations
            yield from $this->compensate();
            throw $e;
        }
    }
}
