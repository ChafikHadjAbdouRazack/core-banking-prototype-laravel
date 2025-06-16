<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Custodian\Connectors\MockBankConnector;
use App\Domain\Custodian\Services\CustodianRegistry;
use Illuminate\Support\ServiceProvider;

class CustodianServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register CustodianRegistry as singleton
        $this->app->singleton(CustodianRegistry::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $registry = app(CustodianRegistry::class);
        
        // Register mock custodian for testing
        if (config('custodian.mock.enabled', true)) {
            $mockConnector = new MockBankConnector(config('custodian.mock', [
                'name' => 'Mock Bank',
                'base_url' => 'https://mock-bank.local',
                'timeout' => 30,
                'debug' => true,
            ]));
            
            $registry->register('mock', $mockConnector);
        }
        
        // Register other custodians from config
        foreach (config('custodian.providers', []) as $name => $config) {
            if (!$config['enabled'] ?? false) {
                continue;
            }
            
            $connectorClass = $config['connector'];
            
            if (!class_exists($connectorClass)) {
                continue;
            }
            
            $connector = new $connectorClass($config);
            $registry->register($name, $connector);
        }
        
        // Set default custodian
        if ($default = config('custodian.default')) {
            $registry->setDefault($default);
        }
    }
}