<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class Transaction extends EloquentStoredEvent
{
    use HasFactory;

    public $table = 'transactions';

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'aggregate_uuid', 'uuid');
    }
}
