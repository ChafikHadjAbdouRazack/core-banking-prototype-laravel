<?php

namespace App\Domain\Account\Workflows;

use Workflow\ActivityStub;
use Workflow\Workflow;

class DestroyAccountWorkflow extends Workflow
{
    /**
     * @param string $uuid
     *
     * @return \Generator
     */
    public function execute( string $uuid ): \Generator
    {
        return yield ActivityStub::make(
            DestroyAccountActivity::class,
            $uuid
        );
    }
}
