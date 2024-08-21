<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\Money;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class MoneyAdded extends ShouldBeStored
{
    public function __construct(
        public readonly Money $money,
    ) {}
}
