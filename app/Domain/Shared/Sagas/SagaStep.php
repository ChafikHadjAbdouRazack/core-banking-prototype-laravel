<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sagas;

abstract class SagaStep
{
    protected string $name;
    protected bool $hasCompensation = false;
    
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    /**
     * Execute the step
     * 
     * @param array $context
     * @return array|null - Additional context to merge, or null
     */
    abstract public function execute(array $context): ?array;
    
    /**
     * Compensate the step (undo its effects)
     * 
     * @param array $context
     * @return void
     */
    public function compensate(array $context): void
    {
        if (!$this->hasCompensation) {
            throw new \BadMethodCallException("Step '{$this->name}' does not support compensation");
        }
        
        $this->doCompensate($context);
    }
    
    /**
     * Implement compensation logic - override in steps that support compensation
     */
    protected function doCompensate(array $context): void
    {
        // Default: no compensation
    }
    
    /**
     * Enable compensation for this step
     */
    protected function enableCompensation(): void
    {
        $this->hasCompensation = true;
    }
    
    /**
     * Check if step has compensation
     */
    public function hasCompensation(): bool
    {
        return $this->hasCompensation;
    }
    
    /**
     * Get step name
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Validate step preconditions
     */
    protected function validatePreconditions(array $context): void
    {
        // Override in subclasses to add validation
    }
    
    /**
     * Log step execution
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        logger()->{$level}($message, array_merge([
            'saga_step' => $this->name,
            'step_class' => static::class,
        ], $context));
    }
}