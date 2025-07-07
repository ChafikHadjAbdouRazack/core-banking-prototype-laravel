<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Asset\Models\Asset;
use App\Models\Account;
use App\Models\Stablecoin;
use App\Models\StablecoinCollateralPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Workflow\WorkflowStub;

class StablecoinOperationsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected Stablecoin $stablecoin;

    protected Asset $collateralAsset;

    protected function setUp(): void
    {
        parent::setUp();

        // Use fake workflows
        WorkflowStub::fake();

        // Enable stablecoins sub-product for tests
        config(['sub_products.stablecoins.enabled' => true]);

        // Mock SubProductService to always return true for stablecoins
        $this->app->bind(\App\Services\SubProductService::class, function () {
            $mock = \Mockery::mock(\App\Services\SubProductService::class);
            $mock->shouldReceive('isEnabled')
                ->with('stablecoins')
                ->andReturn(true);
            $mock->shouldReceive('isFeatureEnabled')
                ->andReturn(true);

            return $mock;
        });

        // Create test data
        $this->user = User::factory()->create();
        $this->account = Account::factory()->create(['user_uuid' => $this->user->uuid]);

        // Create assets
        $this->collateralAsset = Asset::firstOrCreate(
            ['code' => 'USD'],
            [
                'name'      => 'US Dollar',
                'type'      => 'fiat',
                'precision' => 2,
                'is_active' => true,
            ]
        );

        $fusdAsset = Asset::firstOrCreate(
            ['code' => 'FUSD'],
            [
                'name'      => 'Fiat USD Stablecoin',
                'type'      => 'stablecoin',
                'precision' => 2,
                'is_active' => true,
            ]
        );

        // Create stablecoin
        $this->stablecoin = Stablecoin::factory()->create([
            'code'                 => 'FUSD',
            'name'                 => 'FinAegis USD',
            'symbol'               => 'FUSD',
            'peg_asset_code'       => 'USD',
            'precision'            => 2,
            'is_active'            => true,
            'total_supply'         => 0,
            'max_supply'           => 1000000000, // 10M max
            'collateral_ratio'     => 1.5, // 150%
            'min_collateral_ratio' => 1.2, // 120%
            'liquidation_penalty'  => 0.1, // 10%
            'mint_fee'             => 0.001, // 0.1%
            'burn_fee'             => 0.001, // 0.1%
        ]);

        // Give the account some balance for collateral
        \App\Models\AccountBalance::factory()->create([
            'account_uuid' => $this->account->uuid,
            'asset_code'   => 'USD',
            'balance'      => 1000000, // $10,000
        ]);

        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function it_can_mint_stablecoins()
    {
        $response = $this->postJson('/api/v2/stablecoin-operations/mint', [
            'account_uuid'          => $this->account->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 150000, // $1,500 collateral
            'mint_amount'           => 100000, // $1,000 mint (150% collateralization)
        ]);

        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertOk()
            ->assertJson([
                'message' => 'Stablecoin minted successfully',
            ]);

        // Verify position was created
        $this->assertDatabaseHas('stablecoin_collateral_positions', [
            'account_uuid'          => $this->account->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 150000,
            'debt_amount'           => 100000,
            'status'                => 'active',
        ]);

        // Verify account balances
        $this->assertEquals(850000, $this->account->fresh()->getBalance('USD')); // $10,000 - $1,500
        $this->assertEquals(100000, $this->account->fresh()->getBalance('FUSD')); // $1,000 minted
    }

    #[Test]
    public function it_fails_to_mint_with_insufficient_collateral()
    {
        $response = $this->postJson('/api/v2/stablecoin-operations/mint', [
            'account_uuid'          => $this->account->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 100000, // $1,000 collateral
            'mint_amount'           => 100000, // $1,000 mint (only 100% collateralization, need 150%)
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Insufficient collateral. Required ratio: 1.5000, provided ratio: 1',
            ]);
    }

    #[Test]
    public function it_can_burn_stablecoins()
    {
        // First create a position by minting
        $mintResponse = $this->postJson('/api/v2/stablecoin-operations/mint', [
            'account_uuid'          => $this->account->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 150000,
            'mint_amount'           => 100000,
        ]);

        // Ensure mint was successful
        $mintResponse->assertOk();

        // Now burn half
        $response = $this->postJson('/api/v2/stablecoin-operations/burn', [
            'account_uuid'    => $this->account->uuid,
            'stablecoin_code' => 'FUSD',
            'burn_amount'     => 50000, // Burn $500
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Stablecoin burned successfully',
            ]);

        // Verify position was updated
        $position = StablecoinCollateralPosition::where('account_uuid', $this->account->uuid)
            ->where('stablecoin_code', 'FUSD')
            ->first();

        $this->assertEquals(50000, $position->debt_amount); // $500 remaining debt
        $this->assertEquals(75000, $position->collateral_amount); // $750 collateral (proportionally reduced)

        // Verify account balances
        $this->assertEquals(925000, $this->account->fresh()->getBalance('USD')); // Got back $750
        $this->assertEquals(50000, $this->account->fresh()->getBalance('FUSD')); // $500 remaining
    }

    #[Test]
    public function it_fails_to_burn_more_than_debt_amount()
    {
        // First create a position
        $this->postJson('/api/v2/stablecoin-operations/mint', [
            'account_uuid'          => $this->account->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 150000,
            'mint_amount'           => 100000,
        ]);

        // Try to burn more than debt
        $response = $this->postJson('/api/v2/stablecoin-operations/burn', [
            'account_uuid'    => $this->account->uuid,
            'stablecoin_code' => 'FUSD',
            'burn_amount'     => 200000, // Try to burn $2,000 (more than $1,000 debt)
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'error' => 'Cannot burn more than debt amount',
            ]);
    }

    #[Test]
    public function it_can_add_collateral()
    {
        // First create a position
        $this->postJson('/api/v2/stablecoin-operations/mint', [
            'account_uuid'          => $this->account->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 150000,
            'mint_amount'           => 100000,
        ]);

        // Add more collateral
        $response = $this->postJson('/api/v2/stablecoin-operations/add-collateral', [
            'account_uuid'          => $this->account->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 50000, // Add $500 more collateral
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Collateral added successfully',
            ]);

        // Verify position was updated
        $position = StablecoinCollateralPosition::where('account_uuid', $this->account->uuid)
            ->where('stablecoin_code', 'FUSD')
            ->first();

        $this->assertEquals(200000, $position->collateral_amount); // $2,000 total collateral
        $this->assertEquals(100000, $position->debt_amount); // Debt unchanged
    }

    #[Test]
    public function it_can_get_liquidation_opportunities()
    {
        // Create a position that's under-collateralized
        $underCollateralizedAccount = Account::factory()->create();

        // Give the account some balance for collateral
        \App\Models\AccountBalance::factory()->create([
            'account_uuid' => $underCollateralizedAccount->uuid,
            'asset_code'   => 'USD',
            'balance'      => 500000, // $5,000
        ]);

        // Create position manually to bypass collateral checks
        StablecoinCollateralPosition::create([
            'account_uuid'          => $underCollateralizedAccount->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 110000, // $1,100 collateral
            'debt_amount'           => 100000, // $1,000 debt (only 110% ratio, below 120% liquidation threshold)
            'collateral_ratio'      => 1.1000, // 110% ratio
            'status'                => 'active',
        ]);

        $response = $this->getJson('/api/v2/stablecoin-operations/liquidation/opportunities');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'position_uuid',
                        'account_uuid',
                        'stablecoin_code',
                        'eligible',
                        'reward',
                        'penalty',
                        'collateral_seized',
                        'debt_amount',
                        'collateral_asset',
                        'current_ratio',
                        'min_ratio',
                        'priority_score',
                        'health_score',
                    ],
                ],
            ]);

        // Should have at least one liquidation opportunity
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    #[Test]
    public function it_can_handle_empty_liquidation_opportunities()
    {
        // No positions or all healthy positions
        $response = $this->getJson('/api/v2/stablecoin-operations/liquidation/opportunities');

        $response->assertOk()
            ->assertJson([
                'data' => [],
            ]);
    }

    #[Test]
    public function it_requires_authentication()
    {
        // Don't authenticate (don't use actingAs)
        $unauthenticatedUser = User::factory()->create();
        $unauthenticatedAccount = Account::factory()->create(['user_uuid' => $unauthenticatedUser->uuid]);

        $response = $this->postJson('/api/v2/stablecoin-operations/mint', [
            'account_uuid'          => $unauthenticatedAccount->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 150000,
            'mint_amount'           => 100000,
        ]);

        $response->assertUnauthorized();
    }
}
