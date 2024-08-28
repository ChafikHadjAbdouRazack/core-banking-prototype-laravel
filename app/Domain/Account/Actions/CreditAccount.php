<?php

namespace App\Domain\Account\Actions;

use App\Domain\Account\Events\MoneyAdded;
use App\Models\Account;

class CreditAccount extends AccountAction
{
    /**
     * @param \App\Domain\Account\Events\MoneyAdded $event
     *
     * @return \App\Models\Account
     */
    public function __invoke(MoneyAdded $event): Account
    {
        return $this->accountRepository->findByUuid(
            $event->aggregateRootUuid()
        )->addMoney(
            $event->money->amount()
        );
    }
}
