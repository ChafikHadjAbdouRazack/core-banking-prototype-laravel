<?php

namespace App\Domain\Account\Repositories;

use App\Models\Transaction;
use Spatie\EventSourcing\StoredEvents\Repositories\EloquentStoredEventRepository;

final class TransactionsRepository extends EloquentStoredEventRepository
{
    /**
     * @throws \Spatie\EventSourcing\AggregateRoots\Exceptions\InvalidEloquentStoredEventModel
     */
    public function __construct(
        protected string $storedEventModel = Transaction::class
    )
    {
        parent::__construct();
    }
}
