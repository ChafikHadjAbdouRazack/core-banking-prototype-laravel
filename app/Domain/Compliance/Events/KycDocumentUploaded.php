<?php

namespace App\Domain\Compliance\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class KycDocumentUploaded extends ShouldBeStored
{
    public function __construct(
        public string $userUuid,
        public array $document
    ) {}
}