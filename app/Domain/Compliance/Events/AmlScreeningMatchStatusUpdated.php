<?php

/**
 * AML Screening Match Status Updated Event
 *
 * @package App\Domain\Compliance\Events
 */

namespace App\Domain\Compliance\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

/**
 * Event fired when AML screening match status is updated
 */
class AmlScreeningMatchStatusUpdated extends ShouldBeStored
{
    /**
     * Create new AML screening match status updated event
     *
     * @param string $matchId
     * @param string $action
     * @param array $details
     * @param string|null $reason
     */
    public function __construct(
        public string $matchId,
        public string $action,
        public array $details,
        public ?string $reason = null
    ) {
    }
}