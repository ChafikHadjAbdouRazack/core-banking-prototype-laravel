<?php

namespace App\Domain\Compliance\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class RegulatoryReportGenerated extends ShouldBeStored
{
    public function __construct(
        public string $reportType,
        public array $data
    ) {}
}