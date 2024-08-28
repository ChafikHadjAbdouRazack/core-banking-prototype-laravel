<?php

namespace App\Domain\Account\Actions;

use App\Domain\Account\Events\AccountDeleted;

class DeleteAccount extends AccountAction
{
    /**
     * @param \App\Domain\Account\Events\AccountDeleted $event
     *
     * @return void
     */
    public function __invoke(AccountDeleted $event): void
    {
        $this->accountRepository->findByUuid(
            $event->aggregateRootUuid()
        )->delete();
    }
}
