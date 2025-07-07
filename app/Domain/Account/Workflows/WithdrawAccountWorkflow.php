<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use Workflow\ActivityStub;
use Workflow\Workflow;

class WithdrawAccountWorkflow extends Workflow
{
    /**
     * @param \App\Domain\Account\DataObjects\AccountUuid $uuid
     * @param \App\Domain\Account\DataObjects\Money $money
     *
     * @return \Generator
     */
    public function execute(AccountUuid $uuid, Money $money): \Generator
    {
        return yield ActivityStub::make(
            WithdrawAccountActivity::class,
            $uuid,
            $money
        );
    }
}
