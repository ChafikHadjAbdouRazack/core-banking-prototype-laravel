<?php

namespace App\Services\Lending;

use App\Domain\Lending\Services\LoanApplicationService as DomainLoanApplicationService;

/**
 * @deprecated Use App\Domain\Lending\Services\LoanApplicationService instead
 * This class exists for backward compatibility only.
 */
class LoanApplicationService extends DomainLoanApplicationService
{
    // All functionality is inherited from the domain service
}
