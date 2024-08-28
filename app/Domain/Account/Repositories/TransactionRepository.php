<?php

namespace App\Domain\Account\Repositories;

use App\Models\Transaction;
use Spatie\EventSourcing\StoredEvents\Repositories\EloquentStoredEventRepository;

final class TransactionRepository extends EloquentStoredEventRepository implements EventRepository
{
    public function __construct(
        protected string $storedEventModel = Transaction::class
    )
    {
    }
}
