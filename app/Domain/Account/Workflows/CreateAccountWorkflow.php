<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\DataObjects\Account;
use Workflow\ActivityStub;
use Workflow\Workflow;

class CreateAccountWorkflow extends Workflow
{
    /**
     * @param \App\Domain\Account\DataObjects\Account $account
     *
     * @return \Generator
     */
    public function execute( Account $account ): \Generator
    {
        try {
            $result = yield ActivityStub::make(
                CreateAccountActivity::class,
                $account
            );
            
            // Add compensation to delete the created account if workflow fails later
            $this->addCompensation(fn() => ActivityStub::make(
                DeleteAccountActivity::class,
                $account
            ));
            
            return $result;
        } catch (\Throwable $th) {
            yield from $this->compensate();
            throw $th;
        }
    }
}
