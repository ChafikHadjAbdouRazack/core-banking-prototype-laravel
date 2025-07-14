<?php

namespace App\Domain\Compliance\Events;

use App\Domain\Compliance\Models\KycVerification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KycVerificationFailed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly KycVerification $verification,
        public readonly string $reason
    ) {
    }
}
