<?php

namespace App\Domain\Account\Models;

use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class Ledger extends EloquentStoredEvent
{
    public $table = 'ledgers';
}
