<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\Aggregates\LedgerAggregate;
use App\Domain\Account\DataObjects\AccountUuid;
use Workflow\Activity;

class DestroyAccountActivity extends Activity
{
    /**
     * @param \App\Domain\Account\DataObjects\AccountUuid $uuid
     * @param \App\Domain\Account\Aggregates\LedgerAggregate $ledger
     *
     * @return bool
     */
    public function execute(AccountUuid $uuid, LedgerAggregate $ledger): bool
    {
        $ledger->retrieve($uuid->getUuid())
               ->deleteAccount()
               ->persist();

        return true;
    }
}
