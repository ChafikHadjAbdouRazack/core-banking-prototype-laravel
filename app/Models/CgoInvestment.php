<?php

namespace App\Models;

use App\Domain\Cgo\Models\CgoInvestment as DomainCgoInvestment;

/**
 * @deprecated Use App\Domain\Cgo\Models\CgoInvestment instead
 */
class CgoInvestment extends DomainCgoInvestment
{
    // This class is kept for backward compatibility
    // All functionality is inherited from the domain model
}
