<?php

namespace App\Domain\Account\Models;

use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class Transfer extends EloquentStoredEvent
{
    public $table = 'transfers';
}
