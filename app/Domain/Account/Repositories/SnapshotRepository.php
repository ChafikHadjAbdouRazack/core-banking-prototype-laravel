<?php

namespace App\Domain\Account\Repositories;

use App\Domain\Account\Snapshots\AccountSnapshot;
use Spatie\EventSourcing\Snapshots\EloquentSnapshotRepository;

final class SnapshotRepository extends EloquentSnapshotRepository implements EventRepository
{
    public function __construct(
        protected string $storedEventModel = AccountSnapshot::class
    )
    {
    }
}
