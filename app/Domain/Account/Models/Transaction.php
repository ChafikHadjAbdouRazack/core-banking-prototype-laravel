<?php

namespace App\Domain\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class Transaction extends EloquentStoredEvent
{
    use HasFactory;

    public $table = 'transactions';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Database\Factories\TransactionFactory
     */
    protected static function newFactory()
    {
        return \Database\Factories\TransactionFactory::new();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'aggregate_uuid', 'uuid');
    }
}
