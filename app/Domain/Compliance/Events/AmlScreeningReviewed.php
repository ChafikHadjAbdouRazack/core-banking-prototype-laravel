<?php

/**
 * AML Screening Reviewed Event
 *
 * @package App\Domain\Compliance\Events
 */

namespace App\Domain\Compliance\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

/**
 * Event fired when AML screening is reviewed
 */
class AmlScreeningReviewed extends ShouldBeStored
{
    /**
     * Create new AML screening reviewed event
     *
     * @param string $reviewedBy
     * @param string $decision
     * @param string $notes
     */
    public function __construct(
        public string $reviewedBy,
        public string $decision,
        public string $notes
    ) {
    }
}