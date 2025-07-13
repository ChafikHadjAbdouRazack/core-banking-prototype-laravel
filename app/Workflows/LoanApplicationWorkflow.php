<?php

namespace App\Workflows;

use App\Domain\Lending\Workflows\LoanApplicationWorkflow as DomainLoanApplicationWorkflow;

/**
 * @deprecated This class has been moved to App\Domain\Lending\Workflows\LoanApplicationWorkflow
 * This facade exists only for backward compatibility.
 */
class LoanApplicationWorkflow extends DomainLoanApplicationWorkflow
{
    // This class serves as a facade/proxy to maintain backward compatibility
    // All functionality is inherited from the domain class
}