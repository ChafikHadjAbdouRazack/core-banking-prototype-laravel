<?php

namespace App\Domain\Account\Actions;

use App\Domain\Account\Events\AccountUnfrozen;
use App\Models\Account;

class UnfreezeAccount
{
    public function __invoke(AccountUnfrozen $event): void
    {
        $account = Account::query()
            ->where('uuid', $event->aggregateRootUuid())
            ->firstOrFail();

        $account->update(
            [
                'frozen' => false,
            ]
        );
    }
}
