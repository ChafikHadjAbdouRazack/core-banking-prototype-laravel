<?php

namespace App\Domain\Exchange\Workflows\Policies;

use Workflow\Exception\RetryOptions;

class LiquidityRetryPolicy
{
    public static function standard(): RetryOptions
    {
        return RetryOptions::new()
            ->withInitialInterval(1000) // 1 second
            ->withBackoffCoefficient(2.0)
            ->withMaximumInterval(60000) // 60 seconds
            ->withMaximumAttempts(3)
            ->withNonRetryableExceptions([
                \DomainException::class,
                \InvalidArgumentException::class,
            ]);
    }

    public static function external(): RetryOptions
    {
        return RetryOptions::new()
            ->withInitialInterval(2000) // 2 seconds
            ->withBackoffCoefficient(2.0)
            ->withMaximumInterval(120000) // 2 minutes
            ->withMaximumAttempts(5)
            ->withNonRetryableExceptions([
                \DomainException::class,
            ]);
    }

    public static function critical(): RetryOptions
    {
        return RetryOptions::new()
            ->withInitialInterval(500) // 500ms
            ->withBackoffCoefficient(1.5)
            ->withMaximumInterval(30000) // 30 seconds
            ->withMaximumAttempts(10);
    }
}
