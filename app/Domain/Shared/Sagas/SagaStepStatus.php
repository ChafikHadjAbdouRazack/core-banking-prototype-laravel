<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sagas;

enum SagaStepStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case COMPENSATED = 'compensated';
    case COMPENSATION_FAILED = 'compensation_failed';
}