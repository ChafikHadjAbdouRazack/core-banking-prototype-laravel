<?php

namespace App\Domain\Account\Actions;

use App\Domain\Account\Events\AccountFrozen;
use App\Models\Account;

class FreezeAccount
{
    /**
     * @param AccountFrozen $event
     * @return void
     */
    public function __invoke(AccountFrozen $event): void
    {
        $account = Account::query()
            ->where('uuid', $event->aggregateRootUuid())
            ->firstOrFail();

        $account->update([
            'frozen' => true,
        ]);
    }
}