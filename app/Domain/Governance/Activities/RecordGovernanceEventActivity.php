<?php

namespace App\Domain\Governance\Activities;

use Workflow\Activity;
use Illuminate\Support\Facades\Log;

class RecordGovernanceEventActivity extends Activity
{
    /**
     * Execute record governance event activity.
     * 
     * @param array $eventData
     * @return void
     */
    public function execute(array $eventData): void
    {
        // Log the governance event
        Log::info('Governance event recorded', $eventData);
    }
}