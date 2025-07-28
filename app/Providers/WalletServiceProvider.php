<?php

namespace App\Providers;

use App\Domain\Wallet\Contracts\KeyManagementServiceInterface;
use App\Domain\Wallet\Contracts\WalletConnectorInterface;
use App\Domain\Wallet\Contracts\WalletServiceInterface;
use App\Domain\Wallet\Projectors\BlockchainWalletProjector;
use App\Domain\Wallet\Services\BlockchainWalletService;
use App\Domain\Wallet\Services\KeyManagementService;
use App\Domain\Wallet\Services\WalletService;
use Illuminate\Support\ServiceProvider;
use Spatie\EventSourcing\Facades\Projectionist;

class WalletServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->singleton(WalletServiceInterface::class, WalletService::class);
        $this->app->singleton(WalletConnectorInterface::class, BlockchainWalletService::class);
        $this->app->singleton(KeyManagementServiceInterface::class, KeyManagementService::class);

        // Register concrete services (for backward compatibility)
        $this->app->singleton(WalletService::class);
        $this->app->singleton(BlockchainWalletService::class);
        $this->app->singleton(KeyManagementService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register projectors
        Projectionist::addProjectors(
            [
                BlockchainWalletProjector::class,
            ]
        );
    }
}
