<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;
use App\Values\EventQueues;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class MoneyTransferred extends ShouldBeStored implements HasHash, HasMoney
{
    /**
     * @var string
     */
    public string $queue = EventQueues::TRANSFERS->value;

    use HashValidatorProvider;

    /**
     * @param \App\Domain\Account\DataObjects\AccountUuid $from
     * @param \App\Domain\Account\DataObjects\AccountUuid $to
     * @param \App\Domain\Account\DataObjects\Money $money
     * @param \App\Domain\Account\DataObjects\Hash $hash
     */
    public function __construct(
        public readonly AccountUuid $from,
        public readonly AccountUuid $to,
        public readonly Money $money,
        public readonly Hash  $hash,
    ) {
    }
}
