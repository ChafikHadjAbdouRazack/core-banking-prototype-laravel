<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;
use App\Values\EventQueues;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class MoneySubtracted extends ShouldBeStored implements HasHash, HasMoney
{
    /**
     * @var string
     */
    public string $queue = EventQueues::TRANSACTIONS->value;

    /**
     * @param \App\Domain\Account\DataObjects\Money $money
     * @param \App\Domain\Account\DataObjects\Hash $hash
     */
    public function __construct(
        public readonly Money $money,
        public readonly Hash  $hash,
    ) {
    }
}
