<?php

namespace App\Domain\Batch\Events;

use App\Values\EventQueues;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class BatchJobCancelled extends ShouldBeStored
{
    public string $queue = EventQueues::TRANSACTIONS->value;

    public function __construct(
        public readonly string $reason,
        public readonly string $cancelledAt
    ) {
    }
}
