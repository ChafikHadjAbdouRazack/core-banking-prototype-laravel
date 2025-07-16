<?php

namespace App\Domain\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class Transfer extends EloquentStoredEvent
{
    public $table = 'transfers';
}
