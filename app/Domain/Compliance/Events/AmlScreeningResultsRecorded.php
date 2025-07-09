<?php

/**
 * AML Screening Results Recorded Event
 *
 * @package App\Domain\Compliance\Events
 */

namespace App\Domain\Compliance\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

/**
 * Event fired when AML screening results are recorded
 */
class AmlScreeningResultsRecorded extends ShouldBeStored
{
    /**
     * Create new AML screening results recorded event
     *
     * @param array $sanctionsResults
     * @param array $pepResults
     * @param array $adverseMediaResults
     * @param array $otherResults
     * @param int $totalMatches
     * @param string $overallRisk
     * @param array $listsChecked
     * @param array|null $apiResponse
     */
    public function __construct(
        public array $sanctionsResults,
        public array $pepResults,
        public array $adverseMediaResults,
        public array $otherResults,
        public int $totalMatches,
        public string $overallRisk,
        public array $listsChecked,
        public ?array $apiResponse = null
    ) {
    }
}