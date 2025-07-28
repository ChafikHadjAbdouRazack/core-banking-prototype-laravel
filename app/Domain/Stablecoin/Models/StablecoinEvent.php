<?php

namespace App\Domain\Stablecoin\Models;

use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class StablecoinEvent extends EloquentStoredEvent
{
    public $table = 'stablecoin_events';
}
