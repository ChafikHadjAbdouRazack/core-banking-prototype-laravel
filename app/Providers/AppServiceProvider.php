<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment() !== 'testing') {
            $this->app->register(WaterlineServiceProvider::class);
        }

        // Register voting power strategies
        $this->app->bind('asset_weighted_vote', \App\Domain\Governance\Strategies\AssetWeightedVoteStrategy::class);
        $this->app->bind('one_user_one_vote', \App\Domain\Governance\Strategies\OneUserOneVoteStrategy::class);
        $this->app->bind(\App\Domain\Governance\Strategies\AssetWeightedVotingStrategy::class, \App\Domain\Governance\Strategies\AssetWeightedVotingStrategy::class);

        // Register blockchain service provider
        $this->app->register(BlockchainServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register factory namespaces for Domain models
        \Illuminate\Database\Eloquent\Factories\Factory::guessFactoryNamesUsing(function (string $modelName) {
            // First, try the default resolution
            $appNamespace = 'App\\';
            $modelNamespace = 'Models\\';
            
            // If it's a Domain model, map to Database\Factories
            if (str_starts_with($modelName, $appNamespace . 'Domain\\')) {
                $modelBasename = class_basename($modelName);
                return 'Database\\Factories\\' . $modelBasename . 'Factory';
            }
            
            // Default Laravel factory resolution
            $appNamespaceFactoryNamespace = 'Database\\Factories\\';
            $modelName = str_starts_with($modelName, $appNamespace . $modelNamespace)
                ? Str::after($modelName, $appNamespace . $modelNamespace)
                : Str::after($modelName, $appNamespace);

            return $appNamespaceFactoryNamespace . $modelName . 'Factory';
        });
        
        // Treat 'demo' environment as production
        if ($this->app->environment('demo')) {
            // Force production-like settings
            config(['app.debug' => config('demo.debug', false)]);
            config(['app.debug_blacklist' => config('demo.debug_blacklist')]);

            // Force HTTPS in demo environment (but not for local development)
            $localHosts = explode(',', config('app.local_hostnames', 'localhost,127.0.0.1'));
            if (! in_array(request()->getHost(), $localHosts)) {
                \URL::forceScheme('https');
            }

            // Apply demo-specific rate limits
            config(['app.rate_limits.api' => config('demo.rate_limits.api', 60)]);
            config(['app.rate_limits.transactions' => config('demo.rate_limits.transactions', 10)]);
        }
    }
}
