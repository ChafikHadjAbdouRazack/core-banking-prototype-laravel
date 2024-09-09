<?php

namespace App\Domain\Account\Reactors;

use App\Domain\Account\Events\TransactionThresholdReached;
use App\Domain\Account\Aggregates\TransactionAggregate;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

class SnapshotTransactionsReactor extends Reactor
{
    public function onTransactionThresholdReached(
        TransactionThresholdReached $event
    ): void {
        $aggregate = TransactionAggregate::retrieve(
            $event->aggregateRootUuid()
        );
        $aggregate->snapshot();  // Take the snapshot
    }
}
