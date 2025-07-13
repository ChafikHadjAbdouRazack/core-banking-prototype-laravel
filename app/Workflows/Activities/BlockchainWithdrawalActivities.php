<?php

namespace App\Workflows\Activities;

use App\Domain\Wallet\Workflows\Activities\BlockchainWithdrawalActivities as DomainBlockchainWithdrawalActivities;

/**
 * @deprecated Use App\Domain\Wallet\Workflows\Activities\BlockchainWithdrawalActivities instead
 */
class BlockchainWithdrawalActivities extends DomainBlockchainWithdrawalActivities
{
    // This class acts as a proxy to maintain backward compatibility
    // All functionality is inherited from the domain activities
}
