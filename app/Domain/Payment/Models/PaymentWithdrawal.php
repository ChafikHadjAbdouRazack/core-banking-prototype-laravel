<?php

namespace App\Domain\Payment\Models;

use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class PaymentWithdrawal extends EloquentStoredEvent
{
    public $table = 'payment_withdrawals';
}
