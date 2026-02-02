<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\CardIssuance\Adapters\DemoCardIssuerAdapter;
use App\Domain\CardIssuance\Contracts\CardIssuerInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the CardIssuance domain.
 *
 * Binds card issuer contracts to implementations. In production,
 * swap DemoCardIssuerAdapter with Marqeta, Lithic, or Stripe Issuing adapters.
 */
class CardIssuanceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the card issuer interface to the demo adapter
        // For production, create and bind MarqetaCardIssuerAdapter or LithicCardIssuerAdapter
        $this->app->bind(CardIssuerInterface::class, function ($app) {
            return new DemoCardIssuerAdapter();
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
