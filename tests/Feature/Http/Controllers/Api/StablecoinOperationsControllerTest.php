<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\ControllerTestCase;

class StablecoinOperationsControllerTest extends ControllerTestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected Stablecoin $stablecoin;

    protected Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::factory()->create([
            'user_uuid' => $this->user->uuid,
            'balance'   => 1000000, // 10,000.00 in base units
        ]);

        // Create necessary models for the tests
        $this->asset = Asset::firstOrCreate(
            ['code' => 'EUR'],
            [
                'name'      => 'Euro',
                'type'      => 'fiat',
                'precision' => 2,
                'is_active' => true,
            ]
        );

        $this->stablecoin = Stablecoin::firstOrCreate(
            ['code' => 'EURS'],
            [
                'name'                 => 'Euro Stablecoin',
                'symbol'               => 'EURS',
                'peg_asset_code'       => 'EUR',
                'target_price'         => '1.0',
                'stability_mechanism'  => 'collateralized',
                'collateral_ratio'     => '1.5',
                'min_collateral_ratio' => '1.2',
                'liquidation_penalty'  => '0.05',
                'precision'            => 6,
                'is_active'            => true,
            ]
        );
    }

    #[Test]
    public function test_mint_stablecoins_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/stablecoin-operations/mint', [
            'stablecoin_code'       => 'EURS',
            'collateral_asset_code' => 'EUR',
            'collateral_amount'     => 150000, // 1,500.00
            'mint_amount'           => 100000, // 1,000.00
            'account_uuid'          => $this->account->uuid,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ])
            ->assertJson([
                'message' => 'Stablecoin minted successfully',
            ]);
    }

    #[Test]
    public function test_mint_requires_authentication(): void
    {
        $response = $this->postJson('/api/v2/stablecoin-operations/mint');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_mint_validates_input(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/stablecoin-operations/mint', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stablecoin_code', 'collateral_asset_code', 'collateral_amount', 'mint_amount', 'account_uuid']);
    }

    #[Test]
    public function test_burn_stablecoins_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/stablecoin-operations/burn', [
            'account_uuid'    => $this->account->uuid,
            'stablecoin_code' => 'EURS',
            'burn_amount'     => 50000, // 500.00
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ])
            ->assertJson([
                'message' => 'Stablecoin burned successfully',
            ]);
    }

    #[Test]
    public function test_burn_requires_authentication(): void
    {
        $response = $this->postJson('/api/v2/stablecoin-operations/burn');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_burn_validates_input(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/stablecoin-operations/burn', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account_uuid', 'stablecoin_code', 'burn_amount']);
    }

    #[Test]
    public function test_add_collateral_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/stablecoin-operations/add-collateral', [
            'account_uuid'          => $this->account->uuid,
            'stablecoin_code'       => 'EURS',
            'collateral_asset_code' => 'EUR',
            'collateral_amount'     => 20000, // 200.00
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ])
            ->assertJson([
                'message' => 'Collateral added successfully',
            ]);
    }

    #[Test]
    public function test_add_collateral_requires_authentication(): void
    {
        $response = $this->postJson('/api/v2/stablecoin-operations/add-collateral');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_get_account_positions_returns_empty_list(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/stablecoin-operations/accounts/' . $this->account->uuid . '/positions');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    #[Test]
    public function test_get_account_positions_requires_authentication(): void
    {
        $response = $this->getJson('/api/v2/stablecoin-operations/accounts/' . $this->account->uuid . '/positions');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_get_positions_at_risk_returns_empty_list(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/stablecoin-operations/positions/at-risk');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    #[Test]
    public function test_get_positions_at_risk_requires_authentication(): void
    {
        $response = $this->getJson('/api/v2/stablecoin-operations/positions/at-risk');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_get_position_details_returns_404_for_non_existent(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/stablecoin-operations/positions/550e8400-e29b-41d4-a716-446655440000');

        $response->assertStatus(404);
    }

    #[Test]
    public function test_get_position_details_requires_authentication(): void
    {
        $response = $this->getJson('/api/v2/stablecoin-operations/positions/pos-123');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_get_liquidation_opportunities_returns_empty_list(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/stablecoin-operations/liquidation/opportunities');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    #[Test]
    public function test_get_liquidation_opportunities_requires_authentication(): void
    {
        $response = $this->getJson('/api/v2/stablecoin-operations/liquidation/opportunities');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_execute_auto_liquidation_returns_success(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/stablecoin-operations/liquidation/execute');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ]);
    }

    #[Test]
    public function test_execute_auto_liquidation_requires_authentication(): void
    {
        $response = $this->postJson('/api/v2/stablecoin-operations/liquidation/execute');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_liquidate_position_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/stablecoin-operations/liquidation/positions/550e8400-e29b-41d4-a716-446655440000');

        $response->assertStatus(404);
    }

    #[Test]
    public function test_liquidate_position_requires_authentication(): void
    {
        $response = $this->postJson('/api/v2/stablecoin-operations/liquidation/positions/pos-123');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_calculate_liquidation_reward_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/stablecoin-operations/liquidation/positions/550e8400-e29b-41d4-a716-446655440000/reward');

        $response->assertStatus(404);
    }

    #[Test]
    public function test_calculate_liquidation_reward_requires_authentication(): void
    {
        $response = $this->getJson('/api/v2/stablecoin-operations/liquidation/positions/pos-123/reward');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_simulate_mass_liquidation_returns_results(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/stablecoin-operations/simulation/EURS/mass-liquidation', [
            'price_drop_percentage' => 20,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    #[Test]
    public function test_simulate_mass_liquidation_requires_authentication(): void
    {
        $response = $this->postJson('/api/v2/stablecoin-operations/simulation/EURS/mass-liquidation');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_simulate_mass_liquidation_validates_input(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/stablecoin-operations/simulation/EURS/mass-liquidation', [
            'price_drop_percentage' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price_drop_percentage']);
    }
}
