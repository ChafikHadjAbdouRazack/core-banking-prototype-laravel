<?php

namespace App\Models;

use App\Domain\Lending\Models\LoanCollateral as BaseLoanCollateral;

/**
 * @deprecated Use App\Domain\Lending\Models\LoanCollateral instead
 */
class LoanCollateral extends BaseLoanCollateral
{
    // This class extends the domain model for backward compatibility
    // All functionality is inherited from the parent class
}
