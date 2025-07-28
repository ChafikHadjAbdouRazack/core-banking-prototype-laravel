<?php

namespace App\Domain\Compliance\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class KycVerificationCompleted extends ShouldBeStored
{
    public function __construct(
        public string $userUuid,
        public string $level
    ) {
    }
}
