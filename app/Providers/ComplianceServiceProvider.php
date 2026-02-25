<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Compliance\Adapters\ChainalysisAdapter;
use App\Domain\Compliance\Adapters\InternalSanctionsAdapter;
use App\Domain\Compliance\Contracts\SanctionsScreeningInterface;
use App\Domain\Compliance\Services\AmlScreeningService;
use Illuminate\Support\ServiceProvider;

class ComplianceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SanctionsScreeningInterface::class, function ($app) {
            if (config('services.chainalysis.enabled') && ! empty(config('services.chainalysis.api_key'))) {
                return new ChainalysisAdapter(
                    apiKey: (string) config('services.chainalysis.api_key'),
                    baseUrl: (string) config('services.chainalysis.base_url', 'https://api.chainalysis.com/api/sanctions/v2'),
                    timeout: (int) config('services.chainalysis.timeout', 30),
                    retryAttempts: (int) config('services.chainalysis.retry_attempts', 3),
                );
            }

            return new InternalSanctionsAdapter();
        });

        $this->app->singleton(AmlScreeningService::class, function ($app) {
            return new AmlScreeningService(
                sanctionsAdapter: $app->make(SanctionsScreeningInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
