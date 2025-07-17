<?php

namespace App\Domain\Batch\Actions;

use App\Domain\Batch\Events\BatchJobStarted;
use App\Domain\Batch\Models\BatchJob;

class StartBatchJob
{
    public function __invoke(BatchJobStarted $event): void
    {
        BatchJob::where('uuid', $event->aggregateRootUuid())
            ->update(
                [
                    'status' => 'processing',
                    'started_at' => $event->startedAt,
                ]
            );
    }
}
