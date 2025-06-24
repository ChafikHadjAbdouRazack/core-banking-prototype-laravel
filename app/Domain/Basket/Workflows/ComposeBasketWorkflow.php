<?php

namespace App\Domain\Basket\Workflows;

use Workflow\ActivityStub;
use Workflow\Workflow;

class ComposeBasketWorkflow extends Workflow
{
    public function execute(array $input): \Generator
    {
        // For now, use a simple implementation that delegates to the service
        // This maintains the workflow pattern while avoiding complex activity dependencies
        return yield ActivityStub::make(
            'App\Domain\Basket\Activities\ComposeBasketActivity',
            $input
        );
    }

}