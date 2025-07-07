<?php

namespace App\Domain\Account\Actions;

use App\Domain\Account\Events\AccountDeleted;

class DeleteAccount extends AccountAction
{
    /**
     * @param AccountDeleted $event
     *
     * @return bool|null
     */
    public function __invoke(AccountDeleted $event): ?bool
    {
        return $this->accountRepository->findByUuid(
            $event->aggregateRootUuid()
        )->delete();
    }
}
