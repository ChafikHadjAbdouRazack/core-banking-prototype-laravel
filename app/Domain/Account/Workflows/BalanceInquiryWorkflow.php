<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\DataObjects\AccountUuid;
use Workflow\ActivityStub;
use Workflow\Workflow;

class BalanceInquiryWorkflow extends Workflow
{
    /**
     * @param AccountUuid $uuid
     * @param string|null $requestedBy
     *
     * @return \Generator
     */
    public function execute(AccountUuid $uuid, ?string $requestedBy = null): \Generator
    {
        return yield ActivityStub::make(
            BalanceInquiryActivity::class,
            $uuid,
            $requestedBy
        );
    }
}