<?php

namespace App\Domain\Account\Reactors;

use App\Domain\Account\Aggregates\TransferAggregate;
use App\Domain\Account\Events\TransferThresholdReached;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

class SnapshotTransfersReactor extends Reactor
{
    /**
     * @param \App\Domain\Account\Aggregates\TransferAggregate $transfers
     */
    public function __construct(
        protected TransferAggregate $transfers,
    ) {
    }

    /**
     * @param \App\Domain\Account\Events\TransferThresholdReached $event
     *
     * @return void
     */
    public function onTransferThresholdReached(
        TransferThresholdReached $event
    ): void {
        $aggregate = $this->transfers->loadUuid(
            $event->aggregateRootUuid()
        );
        $aggregate->snapshot();  // Take the snapshot
    }
}
