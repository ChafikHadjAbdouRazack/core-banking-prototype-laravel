<?php

namespace App\Domain\Compliance\Events;

use App\Domain\Account\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionBlocked
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Transaction $transaction,
        public readonly array $reasons
    ) {
    }
}
