<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use Workflow\ActivityStub;
use Workflow\Workflow;

class WithdrawAccountWorkflow extends Workflow
{
    /**
     * @param AccountUuid $uuid
     * @param Money $money
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
