<?php

namespace App\Workflows\Activities;

use App\Domain\Wallet\Workflows\Activities\BlockchainDepositActivities as DomainBlockchainDepositActivities;

/**
 * @deprecated Use App\Domain\Wallet\Workflows\Activities\BlockchainDepositActivities instead
 */
class BlockchainDepositActivities extends DomainBlockchainDepositActivities
{
    // This class acts as a proxy to maintain backward compatibility
    // All functionality is inherited from the domain activities
}
