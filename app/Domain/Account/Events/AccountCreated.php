<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\Account;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class AccountCreated extends ShouldBeStored
{
    public function __construct(
        public readonly Account $account
    ) {}
}
