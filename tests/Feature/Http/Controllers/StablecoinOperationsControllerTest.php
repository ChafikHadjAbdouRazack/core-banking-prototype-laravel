<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\ControllerTestCase;

class StablecoinOperationsControllerTest extends ControllerTestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $regularUser;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'stablecoin_operator']);

        // Create users
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super_admin');

        $this->regularUser = User::factory()->create();

        // Create account for admin user
        $this->account = Account::factory()->create([
            'user_uuid' => $this->adminUser->uuid,
            'status'    => 'active',
        ]);
    }

    #[Test]
    public function test_unauthorized_user_cannot_access_stablecoin_operations()
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('stablecoin-operations.index'));

        $response->assertStatus(403);
    }

    #[Test]
    public function test_authorized_user_can_access_stablecoin_operations_index()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('stablecoin-operations.index'));

        $response->assertStatus(200)
            ->assertViewIs('stablecoin-operations.index')
            ->assertViewHas(['stablecoins', 'statistics', 'recentOperations', 'collateral', 'pendingRequests']);
    }

    #[Test]
    public function test_authorized_user_can_access_mint_form()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('stablecoin-operations.mint', ['stablecoin' => 'USDX']));

        $response->assertStatus(200)
            ->assertViewIs('stablecoin-operations.mint')
            ->assertViewHas(['stablecoin', 'stablecoinInfo', 'collateralAssets', 'operatorAccounts']);
    }

    #[Test]
    public function test_authorized_user_can_access_burn_form()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('stablecoin-operations.burn', ['stablecoin' => 'USDX']));

        $response->assertStatus(200)
            ->assertViewIs('stablecoin-operations.burn')
            ->assertViewHas(['stablecoin', 'stablecoinInfo', 'operatorAccounts']);
    }

    #[Test]
    public function test_authorized_user_can_access_history()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('stablecoin-operations.history'));

        $response->assertStatus(200)
            ->assertViewIs('stablecoin-operations.history')
            ->assertViewHas(['operations', 'summary', 'filters']);
    }

    #[Test]
    public function test_mint_validation_fails_with_invalid_data()
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('stablecoin-operations.mint.process'), [
                'stablecoin' => 'INVALID',
                'amount'     => -100,
            ]);

        $response->assertSessionHasErrors(['stablecoin', 'amount']);
    }

    #[Test]
    public function test_burn_validation_fails_with_invalid_data()
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('stablecoin-operations.burn.process'), [
                'stablecoin' => 'INVALID',
                'amount'     => -100,
            ]);

        $response->assertSessionHasErrors(['stablecoin', 'amount']);
    }
}
