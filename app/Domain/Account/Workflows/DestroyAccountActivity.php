<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\Aggregates\LedgerAggregate;
use Workflow\Activity;

class DestroyAccountActivity extends Activity
{
    /**
     * @param string $uuid
     * @param \App\Domain\Account\Aggregates\LedgerAggregate $ledger
     *
     * @return string
     */
    public function execute( string $uuid, LedgerAggregate $ledger ): string
    {
        $ledger->retrieve( $uuid )
               ->deleteAccount()
               ->persist();

        return $uuid;
    }
}
