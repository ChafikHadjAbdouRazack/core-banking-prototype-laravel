<?php

namespace App\Domain\Batch\Models;

use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class BatchEvent extends EloquentStoredEvent
{
    public $table = 'batch_events';
}
