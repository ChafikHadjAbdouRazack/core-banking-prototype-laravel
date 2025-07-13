<?php

namespace App\Domain\Fraud\Events;

use App\Models\FraudScore;
use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChallengeRequired
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Transaction $transaction;

    public FraudScore $fraudScore;

    public function __construct(Transaction $transaction, FraudScore $fraudScore)
    {
        $this->transaction = $transaction;
        $this->fraudScore = $fraudScore;
    }

    /**
     * Get the tags that should be assigned to the event.
     */
    public function tags(): array
    {
        return [
            'fraud',
            'challenge_required',
            'transaction:'.$this->transaction->id,
            'fraud_score:'.$this->fraudScore->id,
            'risk_level:'.$this->fraudScore->risk_level,
        ];
    }
}
