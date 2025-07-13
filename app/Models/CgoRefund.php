<?php

namespace App\Models;

use App\Domain\Cgo\Models\CgoRefund as DomainCgoRefund;

/**
 * @deprecated Use App\Domain\Cgo\Models\CgoRefund instead
 */
class CgoRefund extends DomainCgoRefund
{
    // This class is kept for backward compatibility
    // All functionality is inherited from the domain model
}
