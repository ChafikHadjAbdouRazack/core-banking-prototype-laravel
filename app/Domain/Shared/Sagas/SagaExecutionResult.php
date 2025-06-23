<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sagas;

readonly class SagaExecutionResult
{
    public function __construct(
        public string $sagaId,
        public SagaStatus $status,
        public array $executedSteps,
        public array $context,
        public ?\Throwable $error = null
    ) {}
    
    public function isSuccess(): bool
    {
        return $this->status->isSuccess();
    }
    
    public function isFailure(): bool
    {
        return $this->status->isFailure();
    }
    
    public function isCompensated(): bool
    {
        return $this->status->isCompensated();
    }
    
    public function getExecutedStepsCount(): int
    {
        return count($this->executedSteps);
    }
    
    public function toArray(): array
    {
        return [
            'saga_id' => $this->sagaId,
            'status' => $this->status->value,
            'executed_steps' => $this->executedSteps,
            'context' => $this->context,
            'error' => $this->error?->getMessage(),
        ];
    }
}