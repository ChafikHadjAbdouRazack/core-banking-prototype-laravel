<?php

namespace App\Domain\Account\Events;

use App\Values\EventQueues;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class TransferThresholdReached extends ShouldBeStored
{
    public string $queue = EventQueues::TRANSFERS->value;
}
