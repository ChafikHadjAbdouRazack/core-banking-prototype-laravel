<?php

namespace App\Domain\Account\Repositories;

use App\Models\Ledger;
use Spatie\EventSourcing\AggregateRoots\Exceptions\InvalidEloquentStoredEventModel;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;
use Spatie\EventSourcing\StoredEvents\Repositories\EloquentStoredEventRepository;

final class LedgerRepository extends EloquentStoredEventRepository
{
    /**
     * @param string $storedEventModel
     *
     * @throws InvalidEloquentStoredEventModel
     */
    public function __construct(
        protected string $storedEventModel = Ledger::class
    ) {
        if (! new $this->storedEventModel() instanceof EloquentStoredEvent) {
            throw new InvalidEloquentStoredEventModel("The class {$this->storedEventModel} must extend EloquentStoredEvent");
        }
    }
}
