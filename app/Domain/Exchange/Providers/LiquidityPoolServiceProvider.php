<?php

namespace App\Domain\Exchange\Providers;

use App\Domain\Exchange\LiquidityPool\Reactors\SnapshotLiquidityPoolReactor;
use App\Domain\Exchange\LiquidityPool\Repositories\LiquidityPoolEventRepository;
use App\Domain\Exchange\LiquidityPool\Repositories\LiquidityPoolSnapshotRepository;
use Illuminate\Support\ServiceProvider;
use Spatie\EventSourcing\Facades\Projectionist;

class LiquidityPoolServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repositories
        $this->app->singleton(LiquidityPoolEventRepository::class);
        $this->app->singleton(LiquidityPoolSnapshotRepository::class);
    }

    public function boot(): void
    {
        // Register reactor
        Projectionist::addReactor(SnapshotLiquidityPoolReactor::class);
    }
}