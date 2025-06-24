<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->account = Account::factory()->forUser($this->user)->create();
    }

    public function test_can_show_deposit_form()
    {
        // Create some active assets
        Asset::factory()->count(3)->create(['is_active' => true]);
        Asset::factory()->create(['is_active' => false]); // Should not appear

        $response = $this->actingAs($this->user)->get('/wallet/deposit');

        $response->assertOk()
            ->assertViewIs('wallet.deposit')
            ->assertViewHas('account', $this->account)
            ->assertViewHas('assets');

        // Check that only active assets are passed to view
        $assets = $response->viewData('assets');
        $this->assertCount(3, $assets);
        $this->assertTrue($assets->every(fn($asset) => $asset->is_active));
    }

    public function test_deposit_form_works_without_account()
    {
        $userWithoutAccount = User::factory()->create();
        Asset::factory()->create(['is_active' => true]);

        $response = $this->actingAs($userWithoutAccount)->get('/wallet/deposit');

        $response->assertOk()
            ->assertViewIs('wallet.deposit')
            ->assertViewHas('account', null)
            ->assertViewHas('assets');
    }

    public function test_can_show_withdraw_form()
    {
        // Create some balances for the account
        $asset1 = Asset::factory()->create();
        $asset2 = Asset::factory()->create();
        
        AccountBalance::factory()->create([
            'account_uuid' => $this->account->uuid,
            'asset_code' => $asset1->code,
            'balance' => 1000,
        ]);
        
        AccountBalance::factory()->create([
            'account_uuid' => $this->account->uuid,
            'asset_code' => $asset2->code,
            'balance' => 0, // Should not appear
        ]);

        $response = $this->actingAs($this->user)->get('/wallet/withdraw');

        $response->assertOk()
            ->assertViewIs('wallet.withdraw')
            ->assertViewHas('account', $this->account)
            ->assertViewHas('balances');

        // Check that only balances with positive amounts are shown
        $balances = $response->viewData('balances');
        $this->assertCount(1, $balances);
        $this->assertEquals(1000, $balances->first()->balance);
    }

    public function test_withdraw_form_works_without_account()
    {
        $userWithoutAccount = User::factory()->create();

        $response = $this->actingAs($userWithoutAccount)->get('/wallet/withdraw');

        $response->assertOk()
            ->assertViewIs('wallet.withdraw')
            ->assertViewHas('account', null)
            ->assertViewHas('balances');

        $balances = $response->viewData('balances');
        $this->assertCount(0, $balances);
    }

    public function test_can_show_transfer_form()
    {
        // Create some balances for the account
        $asset1 = Asset::factory()->create();
        $asset2 = Asset::factory()->create();
        
        AccountBalance::factory()->create([
            'account_uuid' => $this->account->uuid,
            'asset_code' => $asset1->code,
            'balance' => 500,
        ]);
        
        AccountBalance::factory()->create([
            'account_uuid' => $this->account->uuid,
            'asset_code' => $asset2->code,
            'balance' => 0, // Should not appear
        ]);

        $response = $this->actingAs($this->user)->get('/wallet/transfer');

        $response->assertOk()
            ->assertViewIs('wallet.transfer')
            ->assertViewHas('account', $this->account)
            ->assertViewHas('balances');

        // Check that only balances with positive amounts are shown
        $balances = $response->viewData('balances');
        $this->assertCount(1, $balances);
        $this->assertEquals(500, $balances->first()->balance);
    }

    public function test_transfer_form_works_without_account()
    {
        $userWithoutAccount = User::factory()->create();

        $response = $this->actingAs($userWithoutAccount)->get('/wallet/transfer');

        $response->assertOk()
            ->assertViewIs('wallet.transfer')
            ->assertViewHas('account', null)
            ->assertViewHas('balances');

        $balances = $response->viewData('balances');
        $this->assertCount(0, $balances);
    }

    public function test_can_show_convert_form()
    {
        // Create some balances and assets
        $asset1 = Asset::factory()->create(['is_active' => true]);
        $asset2 = Asset::factory()->create(['is_active' => true]);
        
        AccountBalance::factory()->create([
            'account_uuid' => $this->account->uuid,
            'asset_code' => $asset1->code,
            'balance' => 750,
        ]);
        
        AccountBalance::factory()->create([
            'account_uuid' => $this->account->uuid,
            'asset_code' => $asset2->code,
            'balance' => 0, // Should not appear in balances
        ]);

        // Create some exchange rates in the database
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
        ]);
        
        ExchangeRate::factory()->create([
            'from_asset_code' => 'EUR',
            'to_asset_code' => 'USD',
            'rate' => 1.18,
        ]);

        $response = $this->actingAs($this->user)->get('/wallet/convert');

        $response->assertOk()
            ->assertViewIs('wallet.convert')
            ->assertViewHas('account', $this->account)
            ->assertViewHas('balances')
            ->assertViewHas('assets')
            ->assertViewHas('rates');

        // Check that only balances with positive amounts are shown
        $balances = $response->viewData('balances');
        $this->assertCount(1, $balances);
        $this->assertEquals(750, $balances->first()->balance);

        // Check that all active assets are shown
        $assets = $response->viewData('assets');
        $this->assertCount(2, $assets);
        $this->assertTrue($assets->every(fn($asset) => $asset->is_active));

        // Check that exchange rates are passed
        $rates = $response->viewData('rates');
        $this->assertNotEmpty($rates);
    }

    public function test_convert_form_works_without_account()
    {
        $userWithoutAccount = User::factory()->create();
        Asset::factory()->create(['is_active' => true]);

        // No need to create exchange rates for this test

        $response = $this->actingAs($userWithoutAccount)->get('/wallet/convert');

        $response->assertOk()
            ->assertViewIs('wallet.convert')
            ->assertViewHas('account', null)
            ->assertViewHas('balances')
            ->assertViewHas('assets')
            ->assertViewHas('rates');

        $balances = $response->viewData('balances');
        $this->assertCount(0, $balances);
    }

    public function test_all_wallet_routes_require_authentication()
    {
        $routes = [
            '/wallet/deposit',
            '/wallet/withdraw', 
            '/wallet/transfer',
            '/wallet/convert',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }
}