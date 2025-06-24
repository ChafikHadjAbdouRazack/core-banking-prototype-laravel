<?php

namespace App\Domain\Compliance\Workflows;

use Workflow\ActivityStub;
use Workflow\Workflow;

class KycSubmissionWorkflow extends Workflow
{
    public function execute(array $input): \Generator
    {
        // For now, use a simple implementation that delegates to the service
        return yield ActivityStub::make(
            'App\Domain\Compliance\Activities\KycSubmissionActivity',
            $input
        );
    }

}