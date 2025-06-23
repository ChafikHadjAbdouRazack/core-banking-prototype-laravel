<?php

namespace App\Domain\Asset\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Carbon\Carbon;

class BasketValueCalculated extends ShouldBeStored
{
    public function __construct(
        public string $basketCode,
        public array $exchangeRates,
        public float $totalValue,
        public Carbon $calculatedAt
    ) {}
}