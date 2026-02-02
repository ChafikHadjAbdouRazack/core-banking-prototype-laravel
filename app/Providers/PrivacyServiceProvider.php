<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Privacy\Contracts\MerkleTreeServiceInterface;
use App\Domain\Privacy\Contracts\ZkProverInterface;
use App\Domain\Privacy\Services\DemoMerkleTreeService;
use App\Domain\Privacy\Services\DemoZkProver;
use App\Domain\Privacy\Services\MerkleTreeService;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Privacy domain.
 *
 * Binds ZK prover and Merkle tree service contracts to implementations.
 * In production, swap with real ZK proof systems like:
 * - Circom/SnarkJS, Noir/Barretenberg
 * - Polygon ID, Galactica Network
 * - RAILGUN privacy pools
 */
class PrivacyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the ZK prover interface based on configuration
        $this->app->bind(ZkProverInterface::class, function ($app) {
            $provider = config('privacy.zk.provider', 'demo');

            return match ($provider) {
                'demo'  => new DemoZkProver(),
                default => new DemoZkProver(), // Fallback to demo
            };
        });

        // Bind the Merkle tree service interface based on configuration
        $this->app->bind(MerkleTreeServiceInterface::class, function ($app) {
            $provider = config('privacy.merkle.provider', 'demo');

            return match ($provider) {
                'demo'       => new DemoMerkleTreeService(),
                'production' => new MerkleTreeService(),
                default      => new DemoMerkleTreeService(), // Fallback to demo
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
