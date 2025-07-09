<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\DataObjects\AccountUuid;
use Workflow\ActivityStub;
use Workflow\Workflow;

class UnfreezeAccountWorkflow extends Workflow
{
    /**
     * @param AccountUuid $uuid
     * @param string      $reason
     * @param string|null $authorizedBy
     *
     * @return \Generator
     */
    public function execute(AccountUuid $uuid, string $reason, ?string $authorizedBy = null): \Generator
    {
        return yield ActivityStub::make(
            UnfreezeAccountActivity::class,
            $uuid,
            $reason,
            $authorizedBy
        );
    }
}
