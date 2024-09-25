<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\DataObjects\Account;
use Workflow\ActivityStub;
use Workflow\Workflow;

class CreateAccountWorkflow extends Workflow
{
    /**
     * @param \App\Domain\Account\DataObjects\Account $account
     */
    public function execute( Account $account )
    {
        return yield ActivityStub::make(
            CreateAccountActivity::class,
            $account
        );
    }
}
