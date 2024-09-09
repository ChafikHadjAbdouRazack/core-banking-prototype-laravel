<?php

namespace App\Domain\Account\Reactors;

use App\Domain\Account\Events\TransactionThresholdReached;
use App\Domain\Account\Aggregates\TransactionAggregate;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

class SnapshotTransactionsReactor extends Reactor
{
    /**
     * @param \App\Domain\Account\Aggregates\TransactionAggregate $transactions
     */
    public function __construct(
        protected TransactionAggregate $transactions,
    ) {
    }

    public function onTransactionThresholdReached(
        TransactionThresholdReached $event
    ): void {
        $aggregate = $this->transactions->loadUuid(
            $event->aggregateRootUuid()
        );
        $aggregate->snapshot();  // Take the snapshot
    }
}
