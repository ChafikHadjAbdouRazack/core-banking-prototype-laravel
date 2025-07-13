<?php

namespace App\Models;

use App\Domain\Lending\Models\LoanRepayment as BaseLoanRepayment;

/**
 * @deprecated Use App\Domain\Lending\Models\LoanRepayment instead
 */
class LoanRepayment extends BaseLoanRepayment
{
    // This class extends the domain model for backward compatibility
    // All functionality is inherited from the parent class
}
