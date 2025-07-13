<?php

namespace App\Workflows\Activities;

use App\Domain\Lending\Workflows\Activities\LoanApplicationActivities as DomainLoanApplicationActivities;

/**
 * @deprecated This class has been moved to App\Domain\Lending\Workflows\Activities\LoanApplicationActivities
 * This facade exists only for backward compatibility.
 */
class LoanApplicationActivities extends DomainLoanApplicationActivities
{
    // This class serves as a facade/proxy to maintain backward compatibility
    // All functionality is inherited from the domain class
}