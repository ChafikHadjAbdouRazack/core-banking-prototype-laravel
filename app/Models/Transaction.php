<?php

namespace App\Models;

use App\Traits\HasDynamicClientTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class Transaction extends EloquentStoredEvent
{
    use HasDynamicClientTable;

    /**
     * @return string
     */
    public function getTable(): string
    {
        return collect(['transactions', $this->getCustomerId()])->implode('_');
    }
}
