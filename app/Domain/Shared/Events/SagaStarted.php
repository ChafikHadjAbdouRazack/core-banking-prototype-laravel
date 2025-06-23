<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

readonly class SagaStarted
{
    public function __construct(
        public string $sagaId,
        public string $sagaType,
        public array $context,
        public \DateTimeImmutable $timestamp
    ) {}
}