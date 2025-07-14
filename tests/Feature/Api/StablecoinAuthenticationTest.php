<?php

namespace Tests\Feature\Api;

use App\Domain\Asset\Models\Asset;
use App\Models\Account;
use App\Domain\Stablecoin\Models\Stablecoin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\DomainTestCase;

class StablecoinAuthenticationTest extends DomainTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable stablecoins sub-product for tests
        config(['sub_products.stablecoins.enabled' => true]);

        // Mock SubProductService to always return true for stablecoins
        $this->app->bind(\App\Domain\Product\Services\SubProductService::class, function () {
            $mock = \Mockery::mock(\App\Domain\Product\Services\SubProductService::class);
            $mock->shouldReceive('isEnabled')
                ->with('stablecoins')
                ->andReturn(true);
            $mock->shouldReceive('isFeatureEnabled')
                ->andReturn(true);

            return $mock;
        });

        // Create required assets and stablecoin for validation to pass
        Asset::firstOrCreate(
            ['code' => 'USD'],
            [
                'name'      => 'US Dollar',
                'type'      => 'fiat',
                'precision' => 2,
                'is_active' => true,
            ]
        );

        Asset::firstOrCreate(
            ['code' => 'FUSD'],
            [
                'name'      => 'Fiat USD Stablecoin',
                'type'      => 'stablecoin',
                'precision' => 2,
                'is_active' => true,
            ]
        );

        Stablecoin::factory()->create([
            'code'           => 'FUSD',
            'name'           => 'FinAegis USD',
            'symbol'         => 'FUSD',
            'peg_asset_code' => 'USD',
            'precision'      => 2,
            'is_active'      => true,
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_mint()
    {
        // Send request without authentication and with empty body
        $response = $this->postJson('/api/v2/stablecoin-operations/mint', []);

        // Should get 401 since we're not authenticated
        $response->assertUnauthorized();
    }

    #[Test]
    public function it_requires_authentication_for_burn()
    {
        // Don't create user/account - just test authentication requirement
        $response = $this->postJson('/api/v2/stablecoin-operations/burn', [
            'account_uuid'    => fake()->uuid(),
            'stablecoin_code' => 'FUSD',
            'burn_amount'     => 50000,
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_requires_authentication_for_add_collateral()
    {
        // Don't create user/account - just test authentication requirement
        $response = $this->postJson('/api/v2/stablecoin-operations/add-collateral', [
            'account_uuid'          => fake()->uuid(),
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 50000,
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_requires_authentication_for_liquidation()
    {
        $response = $this->postJson('/api/v2/stablecoin-operations/liquidation/execute');

        // Debug the response
        if ($response->status() !== 401) {
            dump('Response status:', $response->status());
            dump('Response body:', $response->json());
        }

        $response->assertUnauthorized();
    }
}
