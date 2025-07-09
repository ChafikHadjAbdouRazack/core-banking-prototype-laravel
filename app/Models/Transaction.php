<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class Transaction extends EloquentStoredEvent
{
    use HasFactory;

    public $table = 'transactions';
}
