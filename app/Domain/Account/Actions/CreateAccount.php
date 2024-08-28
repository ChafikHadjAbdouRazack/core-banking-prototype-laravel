<?php

namespace App\Domain\Account\Actions;

use App\Domain\Account\Events\AccountCreated;
use App\Models\Account;

class CreateAccount extends AccountAction
{
    /**
     * @param \App\Domain\Account\Events\AccountCreated $event
     *
     * @return \App\Models\Account
     */
    public function __invoke(AccountCreated $event): Account
    {
        return $this->accountRepository->create(
            $event->account->withUuid(
                $event->aggregateRootUuid()
            )
        );
    }
}
