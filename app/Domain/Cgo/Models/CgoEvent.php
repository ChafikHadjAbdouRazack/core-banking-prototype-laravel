<?php

namespace App\Domain\Cgo\Models;

use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class CgoEvent extends EloquentStoredEvent
{
    public $table = 'cgo_events';
}
