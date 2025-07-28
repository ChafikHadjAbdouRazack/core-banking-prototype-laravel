<?php

namespace App\Domain\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

/**
 * @property int $id
 * @property string|null $aggregate_uuid
 * @property int|null $aggregate_version
 * @property int $event_version
 * @property string $event_class
 * @property array $event_properties
 * @property array $meta_data
 * @property \Illuminate\Support\Carbon $created_at
 */
class Transaction extends EloquentStoredEvent
{
    use HasFactory;

    public $table = 'transactions';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Database\Factories\TransactionFactory
     */
    protected static function newFactory(): \Database\Factories\TransactionFactory
    {
        return \Database\Factories\TransactionFactory::new();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'aggregate_uuid', 'uuid');
    }
}
