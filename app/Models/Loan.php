<?php

namespace App\Models;

use App\Domain\Lending\Models\Loan as BaseLoan;

/**
 * @deprecated Use App\Domain\Lending\Models\Loan instead
 */
class Loan extends BaseLoan
{
    // This class extends the domain model for backward compatibility
    // All functionality is inherited from the parent class
}
