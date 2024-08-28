<?php

namespace App\Domain\Account\Actions;

use App\Domain\Account\Events\MoneySubtracted;

class DebitAccount extends AccountAction
{
    /**
     * @param \App\Domain\Account\Events\MoneySubtracted $event
     *
     * @return void
     */
    public function __invoke(MoneySubtracted $event): void
    {
        $this->accountRepository->findByUuid(
            $event->aggregateRootUuid()
        )->subtractMoney(
            $event->money->amount()
        );
    }
}
