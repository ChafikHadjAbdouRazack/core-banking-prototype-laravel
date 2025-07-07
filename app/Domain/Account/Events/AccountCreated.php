<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\Account;
use App\Values\EventQueues;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class AccountCreated extends ShouldBeStored
{
    /**
     * @var string
     */
    public string $queue = EventQueues::LEDGER->value;

    /**
     * @param \App\Domain\Account\DataObjects\Account $account
     */
    public function __construct(
        public readonly Account $account
    ) {
    }
}
