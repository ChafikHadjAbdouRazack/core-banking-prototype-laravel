<?php

namespace App\Domain\Asset\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class BasketComposed extends ShouldBeStored
{
    public function __construct(
        public string $accountUuid,
        public string $basketCode,
        public int $amount,
        public array $exchangeRates,
        public array $components
    ) {}
}