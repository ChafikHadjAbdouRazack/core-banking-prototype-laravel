<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain;

use App\Infrastructure\Domain\Commands\DomainCreateCommand;
use App\Infrastructure\Domain\Commands\DomainDependenciesCommand;
use App\Infrastructure\Domain\Commands\DomainInstallCommand;
use App\Infrastructure\Domain\Commands\DomainListCommand;
use App\Infrastructure\Domain\Commands\DomainRemoveCommand;
use App\Infrastructure\Domain\Commands\DomainVerifyCommand;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Domain Management infrastructure.
 *
 * Registers the DomainManager and related services for
 * the modular architecture system.
 */
class DomainServiceProvider extends ServiceProvider
{
    /**
     * The commands to register.
     *
     * @var array<class-string>
     */
    protected array $commands = [
        DomainCreateCommand::class,
        DomainListCommand::class,
        DomainInstallCommand::class,
        DomainRemoveCommand::class,
        DomainVerifyCommand::class,
        DomainDependenciesCommand::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(DependencyResolver::class);

        $this->app->singleton(DomainManager::class, function ($app) {
            return new DomainManager(
                dependencyResolver: $app->make(DependencyResolver::class),
                domainBasePath: 'app/Domain',
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<class-string>
     */
    public function provides(): array
    {
        return [
            DependencyResolver::class,
            DomainManager::class,
        ];
    }
}
