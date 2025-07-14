<?php

namespace App\Domain\Exchange\Models;

use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class LiquidityPoolEvent extends EloquentStoredEvent
{
    public $table = 'liquidity_pool_events';
}
