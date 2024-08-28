<?php

namespace App\Domain\Account\Actions;

use App\Domain\Account\Events\MoneyAdded;

class CreditAccount extends AccountAction
{
    /**
     * @param \App\Domain\Account\Events\MoneyAdded $event
     *
     * @return void
     */
    public function __invoke(MoneyAdded $event): void
    {
        $this->accountRepository->findByUuid(
            $event->aggregateRootUuid()
        )->addMoney(
            $event->money->amount()
        );
    }
}
