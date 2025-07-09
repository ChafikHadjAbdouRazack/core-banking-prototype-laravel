<?php

namespace App\Domain\Account\Events;

use App\Values\EventQueues;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class AccountFrozen extends ShouldBeStored
{
    /**
     * @var string
     */
    public string $queue = EventQueues::LEDGER->value;

    /**
     * @param string      $reason
     * @param string|null $authorizedBy
     */
    public function __construct(
        public readonly string $reason,
        public readonly ?string $authorizedBy = null
    ) {
    }
}
