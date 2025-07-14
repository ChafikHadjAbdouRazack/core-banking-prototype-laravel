<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;
use App\Values\EventQueues;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class MoneyAdded extends ShouldBeStored implements HasHash, HasMoney
{
    use HashValidatorProvider;

    public string $queue = EventQueues::TRANSACTIONS->value;

    public function __construct(
        public readonly Money $money,
        public readonly Hash $hash
    ) {
    }
}
