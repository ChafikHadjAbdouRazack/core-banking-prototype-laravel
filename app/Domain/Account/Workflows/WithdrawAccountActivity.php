<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use Workflow\Activity;

class WithdrawAccountActivity extends Activity
{
    /**
     * @param \App\Domain\Account\DataObjects\AccountUuid $uuid
     * @param \App\Domain\Account\DataObjects\Money $money
     * @param \App\Domain\Account\Aggregates\TransactionAggregate $transaction
     *
     * @return bool
     */
    public function execute(AccountUuid $uuid, Money $money, TransactionAggregate $transaction): bool
    {
        $transaction->retrieve($uuid->getUuid())
               ->debit($money)
               ->persist();

        return true;
    }
}
