<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\Aggregates\LedgerAggregate;
use App\Domain\Account\DataObjects\Account;
use Illuminate\Support\Str;
use Workflow\Activity;

class CreateAccountActivity extends Activity
{
    /**
     * @param \App\Domain\Account\DataObjects\Account $account
     * @param \App\Domain\Account\Aggregates\LedgerAggregate $ledger
     *
     * @return string
     */
    public function execute( Account $account, LedgerAggregate $ledger ): string
    {
        $uuid = Str::uuid();

        $ledger->retrieve( $uuid )
               ->createAccount( __account( $account ) )
               ->persist();

        return $uuid;
    }
}
