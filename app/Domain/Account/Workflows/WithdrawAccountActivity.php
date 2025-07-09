<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use Workflow\Activity;

class WithdrawAccountActivity extends Activity
{
    /**
     * @param AccountUuid          $uuid
     * @param Money                $money
     * @param TransactionAggregate $transaction
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
