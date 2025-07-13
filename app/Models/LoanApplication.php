<?php

namespace App\Models;

use App\Domain\Lending\Models\LoanApplication as BaseLoanApplication;

/**
 * @deprecated Use App\Domain\Lending\Models\LoanApplication instead
 */
class LoanApplication extends BaseLoanApplication
{
    // This class extends the domain model for backward compatibility
    // All functionality is inherited from the parent class
}
