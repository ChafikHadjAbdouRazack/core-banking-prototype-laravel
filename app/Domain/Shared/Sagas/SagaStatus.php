<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sagas;

enum SagaStatus: string
{
    case CREATED = 'created';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case COMPENSATING = 'compensating';
    case COMPENSATED = 'compensated';
    case COMPENSATION_FAILED = 'compensation_failed';
    
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::COMPENSATED,
            self::COMPENSATION_FAILED,
        ]);
    }
    
    public function isSuccess(): bool
    {
        return $this === self::COMPLETED;
    }
    
    public function isFailure(): bool
    {
        return in_array($this, [
            self::FAILED,
            self::COMPENSATION_FAILED,
        ]);
    }
    
    public function isCompensated(): bool
    {
        return $this === self::COMPENSATED;
    }
}