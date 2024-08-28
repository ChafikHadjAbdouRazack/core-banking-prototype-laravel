<?php

namespace App\Domain\Account\Actions;

use App\Domain\Account\Events\AccountCreated;

class CreateAccount extends AccountAction
{
    /**
     * @param \App\Domain\Account\Events\AccountCreated $event
     *
     * @return void
     */
    public function __invoke(AccountCreated $event): void
    {
        $this->accountRepository->create(
            $event->account->withUuid(
                $event->aggregateRootUuid()
            )
        );
    }
}
