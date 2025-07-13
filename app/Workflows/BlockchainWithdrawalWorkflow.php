<?php

namespace App\Workflows;

use App\Domain\Wallet\Workflows\BlockchainWithdrawalWorkflow as DomainBlockchainWithdrawalWorkflow;

/**
 * @deprecated Use App\Domain\Wallet\Workflows\BlockchainWithdrawalWorkflow instead
 */
class BlockchainWithdrawalWorkflow extends DomainBlockchainWithdrawalWorkflow
{
    // This class acts as a proxy to maintain backward compatibility
    // All functionality is inherited from the domain workflow
}
