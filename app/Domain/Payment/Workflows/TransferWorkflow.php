<?php

namespace App\Domain\Payment\Workflows;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Workflows\DepositAccountWorkflow;
use App\Domain\Account\Workflows\WithdrawAccountWorkflow;
use Workflow\Workflow;
use Workflow\ChildWorkflowStub;

class TransferWorkflow extends Workflow
{
    /**
     * @param \App\Domain\Account\DataObjects\AccountUuid $from
     * @param \App\Domain\Account\DataObjects\AccountUuid $to
     * @param \App\Domain\Account\DataObjects\Money $money
     *
     * @return \Generator
     * @throws \Throwable
     */
    public function execute(AccountUuid $from, AccountUuid $to, Money $money): \Generator
    {
        try {
            yield ChildWorkflowStub::make(
                WithdrawAccountWorkflow::class,
                $from,
                $money
            );
            $this->addCompensation(fn() => ChildWorkflowStub::make(
                DepositAccountWorkflow::class,
                $from,
                $money
            ));

            yield ChildWorkflowStub::make(
                DepositAccountWorkflow::class,
                $to,
                $money
            );
            $this->addCompensation(fn() => ChildWorkflowStub::make(
                WithdrawAccountWorkflow::class,
                $to,
                $money
            ));
        } catch (\Throwable $th) {
            yield from $this->compensate();
            throw $th;
        }
    }
}
