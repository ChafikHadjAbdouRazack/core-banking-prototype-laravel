<?php

namespace App\Domain\Payment\Models;

use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class PaymentDeposit extends EloquentStoredEvent
{
    public $table = 'payment_deposits';
}
