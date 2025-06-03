<?php

namespace App\Domain\Account\Workflows;

use Workflow\ActivityStub;
use Workflow\Workflow;

class BatchProcessingWorkflow extends Workflow
{
    /**
     * Execute end-of-day batch processing operations
     * 
     * @param array $operations - array of batch operations to perform
     * @param string|null $batchId
     *
     * @return \Generator
     */
    public function execute(array $operations, ?string $batchId = null): \Generator
    {
        $batchId = $batchId ?? \Illuminate\Support\Str::uuid();
        
        try {
            $results = yield ActivityStub::make(
                BatchProcessingActivity::class,
                $operations,
                $batchId
            );
            
            return $results;
        } catch (\Throwable $th) {
            // Log batch processing failure
            logger()->error('Batch processing failed', [
                'batch_id' => $batchId,
                'operations' => $operations,
                'error' => $th->getMessage(),
            ]);
            
            throw $th;
        }
    }
}