<?php

namespace App\Models;

use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class Transaction extends EloquentStoredEvent
{
    public $table = 'transactions';
}
