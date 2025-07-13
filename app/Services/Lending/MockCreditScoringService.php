<?php

namespace App\Services\Lending;

use App\Domain\Lending\Services\MockCreditScoringService as DomainMockCreditScoringService;

/**
 * @deprecated Use App\Domain\Lending\Services\MockCreditScoringService instead
 * This class exists for backward compatibility only.
 */
class MockCreditScoringService extends DomainMockCreditScoringService
{
    // All functionality is inherited from the domain service
}
