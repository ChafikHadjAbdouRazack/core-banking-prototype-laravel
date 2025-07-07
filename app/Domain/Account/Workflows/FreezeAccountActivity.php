<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\Aggregates\LedgerAggregate;
use App\Domain\Account\DataObjects\AccountUuid;
use Workflow\Activity;

class FreezeAccountActivity extends Activity
{
    /**
     * @param AccountUuid $uuid
     * @param string $reason
     * @param string|null $authorizedBy
     * @param LedgerAggregate $ledger
     *
     * @return bool
     */
    public function execute(
        AccountUuid $uuid,
        string $reason,
        ?string $authorizedBy,
        LedgerAggregate $ledger
    ): bool {
        $ledger->retrieve($uuid->getUuid())
               ->freezeAccount($reason, $authorizedBy)
               ->persist();

        return true;
    }
}
