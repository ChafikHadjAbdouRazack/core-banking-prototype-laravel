<?php

namespace App\Workflows;

use App\Domain\Wallet\Workflows\BlockchainDepositWorkflow as DomainBlockchainDepositWorkflow;

/**
 * @deprecated Use App\Domain\Wallet\Workflows\BlockchainDepositWorkflow instead
 */
class BlockchainDepositWorkflow extends DomainBlockchainDepositWorkflow
{
    // This class acts as a proxy to maintain backward compatibility
    // All functionality is inherited from the domain workflow
}
