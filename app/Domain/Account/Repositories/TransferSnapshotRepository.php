<?php

namespace App\Domain\Account\Repositories;

use App\Domain\Account\Snapshots\TransferSnapshot;
use Spatie\EventSourcing\AggregateRoots\Exceptions\InvalidEloquentStoredEventModel;
use Spatie\EventSourcing\Snapshots\EloquentSnapshot;
use Spatie\EventSourcing\Snapshots\EloquentSnapshotRepository;

final class TransferSnapshotRepository extends EloquentSnapshotRepository
{
    /**
     * @param string $snapshotModel
     *
     * @throws InvalidEloquentStoredEventModel
     */
    public function __construct(
        protected string $snapshotModel = TransferSnapshot::class
    ) {
        if (! new $this->snapshotModel() instanceof EloquentSnapshot) {
            throw new InvalidEloquentStoredEventModel("The class {$this->snapshotModel} must extend EloquentStoredEvent");
        }
    }
}
