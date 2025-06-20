<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\BasketAsset;
use App\Models\BasketComponent;
use App\Models\BasketValue;
use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BasketAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected BasketAsset $basket;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->account = Account::factory()->forUser($this->user)->create();
        
        // Create test assets
        Asset::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'type' => 'fiat',
                'precision' => 2,
                'is_active' => true
            ]
        );
        
        Asset::firstOrCreate(
            ['code' => 'EUR'],
            [
                'name' => 'Euro',
                'type' => 'fiat',
                'precision' => 2,
                'is_active' => true
            ]
        );
        
        Asset::firstOrCreate(
            ['code' => 'GBP'],
            [
                'name' => 'British Pound',
                'type' => 'fiat',
                'precision' => 2,
                'is_active' => true
            ]
        );
        
        // Create exchange rates
        ExchangeRate::create([
            'from_asset_code' => 'EUR',
            'to_asset_code' => 'USD',
            'rate' => 1.1,
            'provider' => 'test',
            'valid_at' => now(),
            'updated_at' => now(),
        ]);
        
        ExchangeRate::create([
            'from_asset_code' => 'GBP',
            'to_asset_code' => 'USD',
            'rate' => 1.25,
            'provider' => 'test',
            'valid_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create test basket
        $this->basket = BasketAsset::create([
            'code' => 'TEST_BASKET',
            'name' => 'Test Basket',
            'type' => 'fixed',
            'rebalance_frequency' => 'never',
            'is_active' => true,
        ]);
        
        $this->basket->components()->createMany([
            ['asset_code' => 'USD', 'weight' => 40.0, 'is_active' => true],
            ['asset_code' => 'EUR', 'weight' => 35.0, 'is_active' => true],
            ['asset_code' => 'GBP', 'weight' => 25.0, 'is_active' => true],
        ]);
        
        // Create basket as an asset
        $basketAsset = Asset::firstOrCreate(
            ['code' => 'TEST_BASKET'],
            [
                'name' => 'Test Basket',
                'type' => 'basket',
                'precision' => 4,
                'is_active' => true,
                'is_basket' => true,
            ]
        );
        
        // Set basket value
        BasketValue::create([
            'basket_code' => 'TEST_BASKET',
            'basket_asset_code' => 'TEST_BASKET', // Add missing field
            'value' => 1.0775, // USD 0.40 + EUR 0.385 + GBP 0.3125
            'component_values' => [
                'USD' => 0.40,
                'EUR' => 0.385,
                'GBP' => 0.3125,
            ],
            'calculated_at' => now(),
        ]);
    }

    /** @test */
    public function it_can_decompose_basket_into_components()
    {
        Sanctum::actingAs($this->user);
        
        // Give account basket balance
        $this->account->addBalance('TEST_BASKET', 10000); // 1 basket unit
        
        $response = $this->postJson("/api/v2/accounts/{$this->account->uuid}/baskets/decompose", [
            'basket_code' => 'TEST_BASKET',
            'amount' => 10000,
        ]);
        
        $response->assertOk()
            ->assertJsonStructure([
                'basket_code',
                'basket_amount',
                'components',
                'decomposed_at',
            ])
            ->assertJsonFragment([
                'basket_code' => 'TEST_BASKET',
                'basket_amount' => 10000,
            ]);
        
        // Check basket balance was reduced
        $this->assertEquals(0, $this->account->getBalance('TEST_BASKET'));
        
        // Check component balances were added
        $this->assertGreaterThan(0, $this->account->getBalance('USD'));
        $this->assertGreaterThan(0, $this->account->getBalance('EUR'));
        $this->assertGreaterThan(0, $this->account->getBalance('GBP'));
    }

    /** @test */
    public function it_validates_basket_decomposition()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson("/api/v2/accounts/{$this->account->uuid}/baskets/decompose", []);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['basket_code', 'amount']);
    }

    /** @test */
    public function it_prevents_decomposition_with_insufficient_balance()
    {
        Sanctum::actingAs($this->user);
        
        // Give account less basket balance than requested
        $this->account->addBalance('TEST_BASKET', 5000);
        
        $response = $this->postJson("/api/v2/accounts/{$this->account->uuid}/baskets/decompose", [
            'basket_code' => 'TEST_BASKET',
            'amount' => 10000,
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonStructure(['message']);
    }

    /** @test */
    public function it_prevents_decomposition_of_non_existent_basket()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson("/api/v2/accounts/{$this->account->uuid}/baskets/decompose", [
            'basket_code' => 'NONEXISTENT',
            'amount' => 10000,
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['basket_code']);
    }

    /** @test */
    public function it_can_compose_basket_from_components()
    {
        Sanctum::actingAs($this->user);
        
        // Store initial balances
        $initialUsdBalance = $this->account->getBalance('USD');
        $initialEurBalance = $this->account->getBalance('EUR');
        $initialGbpBalance = $this->account->getBalance('GBP');
        
        // Give account component balances
        // For 1 basket unit (10000), we need components based on weights
        $this->account->addBalance('USD', 4000); // 40% of 10000
        $this->account->addBalance('EUR', 3500); // 35% of 10000
        $this->account->addBalance('GBP', 2500); // 25% of 10000
        
        $response = $this->postJson("/api/v2/accounts/{$this->account->uuid}/baskets/compose", [
            'basket_code' => 'TEST_BASKET',
            'amount' => 10000,
        ]);
        
        $response->assertOk()
            ->assertJsonStructure([
                'basket_code',
                'basket_amount',
                'components_used',
                'composed_at',
            ])
            ->assertJsonFragment([
                'basket_code' => 'TEST_BASKET',
                'basket_amount' => 10000,
            ]);
        
        // Check basket balance was added
        $this->assertEquals(10000, $this->account->getBalance('TEST_BASKET'));
        
        // Check component balances were reduced by exact amounts
        $this->assertEquals($initialUsdBalance, $this->account->getBalance('USD'));
        $this->assertEquals($initialEurBalance, $this->account->getBalance('EUR'));
        $this->assertEquals($initialGbpBalance, $this->account->getBalance('GBP'));
    }

    /** @test */
    public function it_validates_basket_composition()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson("/api/v2/accounts/{$this->account->uuid}/baskets/compose", []);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['basket_code', 'amount']);
    }

    /** @test */
    public function it_prevents_composition_with_insufficient_components()
    {
        Sanctum::actingAs($this->user);
        
        // Give account insufficient component balances
        $this->account->addBalance('USD', 2000); // Only half needed
        $this->account->addBalance('EUR', 1750); // Only half needed
        $this->account->addBalance('GBP', 1250); // Only half needed
        
        $response = $this->postJson("/api/v2/accounts/{$this->account->uuid}/baskets/compose", [
            'basket_code' => 'TEST_BASKET',
            'amount' => 10000,
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonStructure(['message']);
    }

    /** @test */
    public function it_can_get_basket_holdings()
    {
        Sanctum::actingAs($this->user);
        
        // Give account basket balances
        $this->account->addBalance('TEST_BASKET', 20000); // 2 basket units
        
        // Create another basket
        $basket2 = BasketAsset::create([
            'code' => 'ANOTHER_BASKET',
            'name' => 'Another Basket',
            'type' => 'fixed',
            'rebalance_frequency' => 'never',
            'is_active' => true,
        ]);
        
        $basket2->components()->createMany([
            ['asset_code' => 'USD', 'weight' => 50.0],
            ['asset_code' => 'EUR', 'weight' => 50.0],
        ]);
        
        Asset::firstOrCreate(
            ['code' => 'ANOTHER_BASKET'],
            [
                'name' => 'Another Basket',
                'type' => 'basket',
                'precision' => 4,
                'is_active' => true,
                'is_basket' => true,
            ]
        );
        
        BasketValue::create([
            'basket_code' => 'ANOTHER_BASKET',
            'basket_asset_code' => 'ANOTHER_BASKET',
            'value' => 1.05,
            'component_values' => ['USD' => 0.5, 'EUR' => 0.55],
            'calculated_at' => now(),
        ]);
        
        $this->account->addBalance('ANOTHER_BASKET', 10000); // 1 basket unit
        
        $response = $this->getJson("/api/v2/accounts/{$this->account->uuid}/baskets");
        
        $response->assertOk()
            ->assertJsonStructure([
                'account_uuid',
                'basket_holdings',
                'total_value',
                'currency',
            ])
            ->assertJsonFragment(['account_uuid' => $this->account->uuid])
            ->assertJsonCount(2, 'basket_holdings');
    }

    /** @test */
    public function it_returns_empty_holdings_when_no_baskets()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson("/api/v2/accounts/{$this->account->uuid}/baskets");
        
        $response->assertOk()
            ->assertJsonFragment([
                'account_uuid' => $this->account->uuid,
                'basket_holdings' => [],
                'total_value' => 0,
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_decomposition()
    {
        $response = $this->postJson("/api/v2/accounts/{$this->account->uuid}/baskets/decompose", [
            'basket_code' => 'TEST_BASKET',
            'amount' => 10000,
        ]);
        
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_requires_authentication_for_composition()
    {
        $response = $this->postJson("/api/v2/accounts/{$this->account->uuid}/baskets/compose", [
            'basket_code' => 'TEST_BASKET',
            'amount' => 10000,
        ]);
        
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_requires_authentication_for_basket_holdings()
    {
        $response = $this->getJson("/api/v2/accounts/{$this->account->uuid}/baskets");
        
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_other_accounts()
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->forUser($otherUser)->create();
        
        Sanctum::actingAs($this->user);
        
        // Try to decompose basket for another user's account
        $response = $this->postJson("/api/v2/accounts/{$otherAccount->uuid}/baskets/decompose", [
            'basket_code' => 'TEST_BASKET',
            'amount' => 10000,
        ]);
        
        $response->assertForbidden()
            ->assertJsonFragment(['message' => 'Unauthorized']);
        
        // Try to compose basket for another user's account
        $response = $this->postJson("/api/v2/accounts/{$otherAccount->uuid}/baskets/compose", [
            'basket_code' => 'TEST_BASKET',
            'amount' => 10000,
        ]);
        
        $response->assertForbidden()
            ->assertJsonFragment(['message' => 'Unauthorized']);
        
        // Try to get basket holdings for another user's account
        $response = $this->getJson("/api/v2/accounts/{$otherAccount->uuid}/baskets");
        
        $response->assertForbidden()
            ->assertJsonFragment(['message' => 'Unauthorized']);
    }

    /** @test */
    public function it_handles_account_not_found()
    {
        Sanctum::actingAs($this->user);
        
        $nonExistentUuid = '00000000-0000-0000-0000-000000000000';
        
        $response = $this->postJson("/api/v2/accounts/{$nonExistentUuid}/baskets/decompose", [
            'basket_code' => 'TEST_BASKET',
            'amount' => 10000,
        ]);
        
        $response->assertNotFound();
    }

    /** @test */
    public function it_validates_minimum_amount()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson("/api/v2/accounts/{$this->account->uuid}/baskets/decompose", [
            'basket_code' => 'TEST_BASKET',
            'amount' => 0,
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
        
        $response = $this->postJson("/api/v2/accounts/{$this->account->uuid}/baskets/compose", [
            'basket_code' => 'TEST_BASKET',
            'amount' => -100,
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function it_handles_inactive_basket()
    {
        Sanctum::actingAs($this->user);
        
        // Deactivate basket
        $this->basket->update(['is_active' => false]);
        
        // Give account basket balance
        $this->account->addBalance('TEST_BASKET', 10000);
        
        $response = $this->postJson("/api/v2/accounts/{$this->account->uuid}/baskets/decompose", [
            'basket_code' => 'TEST_BASKET',
            'amount' => 10000,
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonStructure(['message']);
    }

    /** @test */
    public function it_handles_basket_with_inactive_components()
    {
        Sanctum::actingAs($this->user);
        
        // Deactivate one component
        $this->basket->components()->where('asset_code', 'EUR')->update(['is_active' => false]);
        
        // Give account basket balance
        $this->account->addBalance('TEST_BASKET', 10000);
        
        $response = $this->postJson("/api/v2/accounts/{$this->account->uuid}/baskets/decompose", [
            'basket_code' => 'TEST_BASKET',
            'amount' => 10000,
        ]);
        
        $response->assertOk();
        
        // Should only decompose to active components
        $this->assertGreaterThan(0, $this->account->getBalance('USD'));
        $this->assertEquals(0, $this->account->getBalance('EUR')); // Inactive component
        $this->assertGreaterThan(0, $this->account->getBalance('GBP'));
    }
}