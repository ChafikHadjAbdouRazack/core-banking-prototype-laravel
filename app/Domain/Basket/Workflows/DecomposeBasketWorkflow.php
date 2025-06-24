<?php

namespace App\Domain\Basket\Workflows;

use Workflow\ActivityStub;
use Workflow\Workflow;

class DecomposeBasketWorkflow extends Workflow
{
    public function execute(array $input): \Generator
    {
        // For now, use a simple implementation that delegates to the service
        return yield ActivityStub::make(
            'App\Domain\Basket\Activities\DecomposeBasketActivity',
            $input
        );
    }

}