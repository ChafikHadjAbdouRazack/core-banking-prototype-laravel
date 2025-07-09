<?php

/**
 * AML Screening Aggregate for event sourcing
 *
 * @package App\Domain\Compliance\Aggregates
 */

namespace App\Domain\Compliance\Aggregates;

use App\Domain\Compliance\Events\AmlScreeningCompleted;
use App\Domain\Compliance\Events\AmlScreeningMatchStatusUpdated;
use App\Domain\Compliance\Events\AmlScreeningResultsRecorded;
use App\Domain\Compliance\Events\AmlScreeningReviewed;
use App\Domain\Compliance\Events\AmlScreeningStarted;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

/**
 * AML Screening Aggregate handles anti-money laundering screening processes
 * using event sourcing patterns.
 */
class AmlScreeningAggregate extends AggregateRoot
{
    private string $_entityId;
    private string $_entityType;
    private string $_screeningNumber;
    private string $_type;
    private string $_status = 'pending';
    private string $_provider;
    private ?string $_providerReference = null;
    private array $_searchParameters = [];
    private array $_results = [];
    private int $_totalMatches = 0;
    private int $_confirmedMatches = 0;
    private int $_falsePositives = 0;
    private ?string $_overallRisk = null;
    private ?string $_reviewedBy = null;
    private ?string $_reviewDecision = null;
    private ?string $_reviewNotes = null;

    /**
     * Start a new AML screening
     *
     * @param string $entityId Entity identifier
     * @param string $entityType Entity type (user, company, etc.)
     * @param string $screeningNumber Unique screening number
     * @param string $type Screening type (sanctions, pep, etc.)
     * @param string $provider Screening provider name
     * @param array $searchParameters Search parameters
     * @param string|null $providerReference Provider's reference ID
     * @return self
     */
    public function startScreening(
        string $entityId,
        string $entityType,
        string $screeningNumber,
        string $type,
        string $provider,
        array $searchParameters,
        ?string $providerReference = null
    ): self {
        $this->recordThat(
            new AmlScreeningStarted(
                $entityId,
                $entityType,
                $screeningNumber,
                $type,
                $provider,
                $searchParameters,
                $providerReference
            )
        );

        return $this;
    }

    /**
     * Record screening results from provider
     *
     * @param array $sanctionsResults Sanctions screening results
     * @param array $pepResults PEP screening results
     * @param array $adverseMediaResults Adverse media results
     * @param array $otherResults Other screening results
     * @param int $totalMatches Total number of matches
     * @param string $overallRisk Overall risk level
     * @param array $listsChecked Lists checked during screening
     * @param array|null $apiResponse Raw API response
     * @return self
     */
    public function recordResults(
        array $sanctionsResults,
        array $pepResults,
        array $adverseMediaResults,
        array $otherResults,
        int $totalMatches,
        string $overallRisk,
        array $listsChecked,
        ?array $apiResponse = null
    ): self {
        $this->recordThat(
            new AmlScreeningResultsRecorded(
                $sanctionsResults,
                $pepResults,
                $adverseMediaResults,
                $otherResults,
                $totalMatches,
                $overallRisk,
                $listsChecked,
                $apiResponse
            )
        );

        return $this;
    }

    /**
     * Update match status (confirm, dismiss, or investigate)
     *
     * @param string $matchId Match identifier
     * @param string $action Action to take (confirm, dismiss, investigate)
     * @param array $details Additional details about the action
     * @param string|null $reason Reason for the action
     * @return self
     * @throws \InvalidArgumentException
     */
    public function updateMatchStatus(
        string $matchId,
        string $action,
        array $details,
        ?string $reason = null
    ): self {
        if (!in_array($action, ['confirm', 'dismiss', 'investigate'])) {
            throw new \InvalidArgumentException(
                'Invalid action. Must be confirm, dismiss, or investigate.'
            );
        }

        $this->recordThat(
            new AmlScreeningMatchStatusUpdated(
                $matchId,
                $action,
                $details,
                $reason
            )
        );

        return $this;
    }

    /**
     * Complete the screening
     *
     * @param string $finalStatus Final status (completed or failed)
     * @param float|null $processingTime Processing time in seconds
     * @return self
     * @throws \InvalidArgumentException
     */
    public function completeScreening(
        string $finalStatus,
        ?float $processingTime = null
    ): self {
        if (!in_array($finalStatus, ['completed', 'failed'])) {
            throw new \InvalidArgumentException(
                'Invalid status. Must be completed or failed.'
            );
        }

        $this->recordThat(
            new AmlScreeningCompleted(
                $finalStatus,
                $processingTime
            )
        );

        return $this;
    }

    /**
     * Review screening results
     *
     * @param string $reviewedBy Reviewer identifier
     * @param string $decision Review decision (clear, escalate, block)
     * @param string $notes Review notes
     * @return self
     * @throws \InvalidArgumentException
     */
    public function reviewScreening(
        string $reviewedBy,
        string $decision,
        string $notes
    ): self {
        if (!in_array($decision, ['clear', 'escalate', 'block'])) {
            throw new \InvalidArgumentException(
                'Invalid decision. Must be clear, escalate, or block.'
            );
        }

        $this->recordThat(
            new AmlScreeningReviewed(
                $reviewedBy,
                $decision,
                $notes
            )
        );

        return $this;
    }

    /**
     * Apply event handlers
     *
     * @param AmlScreeningStarted $event
     * @return void
     */
    protected function applyAmlScreeningStarted(AmlScreeningStarted $event): void
    {
        $this->_entityId = $event->entityId;
        $this->_entityType = $event->entityType;
        $this->_screeningNumber = $event->screeningNumber;
        $this->_type = $event->type;
        $this->_provider = $event->provider;
        $this->_searchParameters = $event->searchParameters;
        $this->_providerReference = $event->providerReference;
        $this->_status = 'in_progress';
    }

    /**
     * Apply AML screening results recorded event
     *
     * @param AmlScreeningResultsRecorded $event
     * @return void
     */
    protected function applyAmlScreeningResultsRecorded(
        AmlScreeningResultsRecorded $event
    ): void {
        $this->_results = [
            'sanctions' => $event->sanctionsResults,
            'pep' => $event->pepResults,
            'adverse_media' => $event->adverseMediaResults,
            'other' => $event->otherResults,
        ];
        $this->_totalMatches = $event->totalMatches;
        $this->_overallRisk = $event->overallRisk;
    }

    /**
     * Apply AML screening match status updated event
     *
     * @param AmlScreeningMatchStatusUpdated $event
     * @return void
     */
    protected function applyAmlScreeningMatchStatusUpdated(
        AmlScreeningMatchStatusUpdated $event
    ): void {
        if ($event->action === 'confirm') {
            $this->_confirmedMatches++;
        } elseif ($event->action === 'dismiss') {
            $this->_falsePositives++;
        }
    }

    /**
     * Apply AML screening completed event
     *
     * @param AmlScreeningCompleted $event
     * @return void
     */
    protected function applyAmlScreeningCompleted(AmlScreeningCompleted $event): void
    {
        $this->_status = $event->finalStatus;
    }

    /**
     * Apply AML screening reviewed event
     *
     * @param AmlScreeningReviewed $event
     * @return void
     */
    protected function applyAmlScreeningReviewed(AmlScreeningReviewed $event): void
    {
        $this->_reviewedBy = $event->reviewedBy;
        $this->_reviewDecision = $event->decision;
        $this->_reviewNotes = $event->notes;
    }

    /**
     * Getters for aggregate state
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->_status;
    }

    /**
     * Get total matches count
     *
     * @return int
     */
    public function getTotalMatches(): int
    {
        return $this->_totalMatches;
    }

    /**
     * Get confirmed matches count
     *
     * @return int
     */
    public function getConfirmedMatches(): int
    {
        return $this->_confirmedMatches;
    }

    /**
     * Get false positives count
     *
     * @return int
     */
    public function getFalsePositives(): int
    {
        return $this->_falsePositives;
    }

    /**
     * Get overall risk level
     *
     * @return string|null
     */
    public function getOverallRisk(): ?string
    {
        return $this->_overallRisk;
    }

    /**
     * Check if screening has been reviewed
     *
     * @return bool
     */
    public function isReviewed(): bool
    {
        return $this->_reviewedBy !== null;
    }

    /**
     * Check if screening requires review
     *
     * @return bool
     */
    public function requiresReview(): bool
    {
        return $this->_totalMatches > 0 && !$this->isReviewed();
    }
}