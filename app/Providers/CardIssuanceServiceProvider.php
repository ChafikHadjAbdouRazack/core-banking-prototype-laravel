<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\CardIssuance\Adapters\DemoCardIssuerAdapter;
use App\Domain\CardIssuance\Adapters\MarqetaCardIssuerAdapter;
use App\Domain\CardIssuance\Contracts\CardIssuerInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the CardIssuance domain.
 *
 * Binds card issuer contracts to implementations based on configuration.
 * Supports demo, Marqeta, and future adapters (Lithic, Stripe Issuing).
 */
class CardIssuanceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CardIssuerInterface::class, function ($app) {
            $issuer = config('cardissuance.default_issuer', 'demo');

            return match ($issuer) {
                'marqeta' => new MarqetaCardIssuerAdapter(
                    config: (array) config('cardissuance.issuers.marqeta', []),
                ),
                default => new DemoCardIssuerAdapter(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
