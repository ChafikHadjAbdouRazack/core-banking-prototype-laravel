<?php

/**
 * AML Screening Started Event
 *
 * @package App\Domain\Compliance\Events
 */

namespace App\Domain\Compliance\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

/**
 * Event fired when AML screening is started
 */
class AmlScreeningStarted extends ShouldBeStored
{
    /**
     * Create new AML screening started event
     *
     * @param string $entityId
     * @param string $entityType
     * @param string $screeningNumber
     * @param string $type
     * @param string $provider
     * @param array $searchParameters
     * @param string|null $providerReference
     */
    public function __construct(
        public string $entityId,
        public string $entityType,
        public string $screeningNumber,
        public string $type,
        public string $provider,
        public array $searchParameters,
        public ?string $providerReference = null
    ) {
    }
}