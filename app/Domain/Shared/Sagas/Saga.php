<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sagas;

use App\Domain\Shared\Sagas\SagaStep;
use App\Domain\Shared\Sagas\SagaStepStatus;
use App\Domain\Shared\Sagas\SagaStatus;
use App\Domain\Shared\Events\SagaStarted;
use App\Domain\Shared\Events\SagaCompleted;
use App\Domain\Shared\Events\SagaFailed;
use App\Domain\Shared\Events\SagaStepExecuted;
use App\Domain\Shared\Events\SagaStepCompensated;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

abstract class Saga
{
    protected string $sagaId;
    protected SagaStatus $status;
    protected array $steps = [];
    protected array $context = [];
    protected array $executedSteps = [];
    protected ?\Throwable $lastError = null;
    protected \DateTimeImmutable $startedAt;
    protected ?\DateTimeImmutable $completedAt = null;
    
    public function __construct(array $context = [])
    {
        $this->sagaId = Str::uuid()->toString();
        $this->status = SagaStatus::CREATED;
        $this->context = $context;
        $this->startedAt = new \DateTimeImmutable();
        
        $this->defineSteps();
    }
    
    /**
     * Define the saga steps - must be implemented by concrete sagas
     */
    abstract protected function defineSteps(): void;
    
    /**
     * Execute the saga
     */
    public function execute(): SagaExecutionResult
    {
        try {
            $this->status = SagaStatus::RUNNING;
            
            event(new SagaStarted(
                sagaId: $this->sagaId,
                sagaType: static::class,
                context: $this->context,
                timestamp: $this->startedAt
            ));
            
            Log::info('Saga execution started', [
                'saga_id' => $this->sagaId,
                'saga_type' => static::class,
                'steps_count' => count($this->steps),
            ]);
            
            foreach ($this->steps as $index => $step) {
                $this->executeStep($step, $index);
            }
            
            $this->status = SagaStatus::COMPLETED;
            $this->completedAt = new \DateTimeImmutable();
            
            event(new SagaCompleted(
                sagaId: $this->sagaId,
                sagaType: static::class,
                context: $this->context,
                executedSteps: $this->executedSteps,
                timestamp: $this->completedAt
            ));
            
            Log::info('Saga execution completed successfully', [
                'saga_id' => $this->sagaId,
                'executed_steps' => count($this->executedSteps),
                'duration_ms' => $this->startedAt->diff($this->completedAt)->format('%f') * 1000,
            ]);
            
            return new SagaExecutionResult(
                sagaId: $this->sagaId,
                status: $this->status,
                executedSteps: $this->executedSteps,
                context: $this->context
            );
            
        } catch (\Throwable $e) {
            return $this->handleFailure($e);
        }
    }
    
    /**
     * Execute a single step
     */
    protected function executeStep(SagaStep $step, int $index): void
    {
        try {
            Log::debug('Executing saga step', [
                'saga_id' => $this->sagaId,
                'step_index' => $index,
                'step_name' => $step->getName(),
                'step_type' => get_class($step),
            ]);
            
            $startTime = microtime(true);
            $result = $step->execute($this->context);
            $executionTime = microtime(true) - $startTime;
            
            // Update context with step result if provided
            if ($result !== null) {
                $this->context = array_merge($this->context, $result);
            }
            
            $stepExecution = [
                'step_index' => $index,
                'step_name' => $step->getName(),
                'step_class' => get_class($step),
                'status' => SagaStepStatus::COMPLETED,
                'execution_time_ms' => round($executionTime * 1000, 2),
                'executed_at' => new \DateTimeImmutable(),
                'result' => $result,
            ];
            
            $this->executedSteps[] = $stepExecution;
            
            event(new SagaStepExecuted(
                sagaId: $this->sagaId,
                stepName: $step->getName(),
                stepIndex: $index,
                context: $this->context,
                result: $result,
                timestamp: new \DateTimeImmutable()
            ));
            
            Log::debug('Saga step completed', [
                'saga_id' => $this->sagaId,
                'step_index' => $index,
                'step_name' => $step->getName(),
                'execution_time_ms' => $stepExecution['execution_time_ms'],
            ]);
            
        } catch (\Throwable $e) {
            Log::error('Saga step failed', [
                'saga_id' => $this->sagaId,
                'step_index' => $index,
                'step_name' => $step->getName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->executedSteps[] = [
                'step_index' => $index,
                'step_name' => $step->getName(),
                'step_class' => get_class($step),
                'status' => SagaStepStatus::FAILED,
                'error' => $e->getMessage(),
                'executed_at' => new \DateTimeImmutable(),
            ];
            
            throw $e;
        }
    }
    
    /**
     * Handle saga failure and execute compensations
     */
    protected function handleFailure(\Throwable $e): SagaExecutionResult
    {
        $this->status = SagaStatus::COMPENSATING;
        $this->lastError = $e;
        
        Log::error('Saga failed, starting compensation', [
            'saga_id' => $this->sagaId,
            'error' => $e->getMessage(),
            'executed_steps' => count($this->executedSteps),
        ]);
        
        try {
            $this->compensate();
            $this->status = SagaStatus::COMPENSATED;
            
            Log::info('Saga compensation completed', [
                'saga_id' => $this->sagaId,
                'compensated_steps' => count($this->executedSteps),
            ]);
            
        } catch (\Throwable $compensationError) {
            $this->status = SagaStatus::COMPENSATION_FAILED;
            
            Log::critical('Saga compensation failed', [
                'saga_id' => $this->sagaId,
                'original_error' => $e->getMessage(),
                'compensation_error' => $compensationError->getMessage(),
                'trace' => $compensationError->getTraceAsString(),
            ]);
        }
        
        event(new SagaFailed(
            sagaId: $this->sagaId,
            sagaType: static::class,
            context: $this->context,
            executedSteps: $this->executedSteps,
            error: $e,
            compensationStatus: $this->status,
            timestamp: new \DateTimeImmutable()
        ));
        
        return new SagaExecutionResult(
            sagaId: $this->sagaId,
            status: $this->status,
            executedSteps: $this->executedSteps,
            context: $this->context,
            error: $e
        );
    }
    
    /**
     * Execute compensation for all executed steps in reverse order
     */
    protected function compensate(): void
    {
        $compensatedSteps = [];
        
        // Compensate in reverse order
        foreach (array_reverse($this->executedSteps) as $stepExecution) {
            if ($stepExecution['status'] !== SagaStepStatus::COMPLETED) {
                continue; // Skip failed steps
            }
            
            $stepIndex = $stepExecution['step_index'];
            $step = $this->steps[$stepIndex];
            
            if (!$step->hasCompensation()) {
                Log::debug('Step has no compensation, skipping', [
                    'saga_id' => $this->sagaId,
                    'step_name' => $step->getName(),
                ]);
                continue;
            }
            
            try {
                Log::debug('Compensating saga step', [
                    'saga_id' => $this->sagaId,
                    'step_name' => $step->getName(),
                ]);
                
                $step->compensate($this->context);
                
                $compensatedSteps[] = [
                    'step_name' => $step->getName(),
                    'compensated_at' => new \DateTimeImmutable(),
                    'status' => 'compensated',
                ];
                
                event(new SagaStepCompensated(
                    sagaId: $this->sagaId,
                    stepName: $step->getName(),
                    stepIndex: $stepIndex,
                    context: $this->context,
                    timestamp: new \DateTimeImmutable()
                ));
                
                Log::debug('Saga step compensated successfully', [
                    'saga_id' => $this->sagaId,
                    'step_name' => $step->getName(),
                ]);
                
            } catch (\Throwable $e) {
                Log::error('Step compensation failed', [
                    'saga_id' => $this->sagaId,
                    'step_name' => $step->getName(),
                    'error' => $e->getMessage(),
                ]);
                
                $compensatedSteps[] = [
                    'step_name' => $step->getName(),
                    'compensated_at' => new \DateTimeImmutable(),
                    'status' => 'compensation_failed',
                    'error' => $e->getMessage(),
                ];
                
                throw $e;
            }
        }
        
        $this->context['compensated_steps'] = $compensatedSteps;
    }
    
    /**
     * Add a step to the saga
     */
    protected function addStep(SagaStep $step): void
    {
        $this->steps[] = $step;
    }
    
    /**
     * Get saga ID
     */
    public function getSagaId(): string
    {
        return $this->sagaId;
    }
    
    /**
     * Get saga status
     */
    public function getStatus(): SagaStatus
    {
        return $this->status;
    }
    
    /**
     * Get saga context
     */
    public function getContext(): array
    {
        return $this->context;
    }
    
    /**
     * Get executed steps
     */
    public function getExecutedSteps(): array
    {
        return $this->executedSteps;
    }
    
    /**
     * Get last error
     */
    public function getLastError(): ?\Throwable
    {
        return $this->lastError;
    }
    
    /**
     * Get execution duration in milliseconds
     */
    public function getExecutionDuration(): ?float
    {
        if ($this->completedAt === null) {
            return null;
        }
        
        return $this->startedAt->diff($this->completedAt)->format('%f') * 1000;
    }
}