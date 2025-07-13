<?php

namespace App\Services\Lending;

use App\Domain\Lending\Services\DefaultCollateralManagementService as DomainDefaultCollateralManagementService;

/**
 * @deprecated Use App\Domain\Lending\Services\DefaultCollateralManagementService instead
 * This class exists for backward compatibility only.
 */
class DefaultCollateralManagementService extends DomainDefaultCollateralManagementService
{
    // All functionality is inherited from the domain service
}
