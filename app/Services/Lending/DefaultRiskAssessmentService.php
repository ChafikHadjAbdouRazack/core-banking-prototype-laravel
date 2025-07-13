<?php

namespace App\Services\Lending;

use App\Domain\Lending\Services\DefaultRiskAssessmentService as DomainDefaultRiskAssessmentService;

/**
 * @deprecated Use App\Domain\Lending\Services\DefaultRiskAssessmentService instead
 * This class exists for backward compatibility only.
 */
class DefaultRiskAssessmentService extends DomainDefaultRiskAssessmentService
{
    // All functionality is inherited from the domain service
}
