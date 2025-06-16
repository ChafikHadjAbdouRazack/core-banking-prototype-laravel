<?php

declare(strict_types=1);

namespace Tests\Feature\Basket;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\BasketAsset;
use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Models\ExchangeRate;
use App\Models\User;
use App\Domain\Basket\Services\BasketAccountService;
use App\Domain\Basket\Services\BasketValueCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasketAccountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BasketAccountService $service;
    protected Account $account;
    protected BasketAsset $basket;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(BasketAccountService::class);
        
        // Create test user and account
        $user = User::factory()->create();
        $this->account = Account::factory()->create(['user_uuid' => $user->uuid]);
        
        // Create test assets (use firstOrCreate to avoid conflicts)
        Asset::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]);
        Asset::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]);
        Asset::firstOrCreate(['code' => 'GBP'], ['name' => 'British Pound', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]);
        Asset::firstOrCreate(['code' => 'STABLE_BASKET'], ['name' => 'Stable Basket', 'type' => 'custom', 'precision' => 4, 'is_active' => true]);
        
        // Create exchange rates
        ExchangeRate::factory()->create([
            'from_asset_code' => 'EUR',
            'to_asset_code' => 'USD',
            'rate' => 1.1000,
            'is_active' => true,
        ]);
        
        ExchangeRate::factory()->create([
            'from_asset_code' => 'GBP',
            'to_asset_code' => 'USD',
            'rate' => 1.2500,
            'is_active' => true,
        ]);
        
        // Create test basket
        $this->basket = BasketAsset::create([
            'code' => 'STABLE_BASKET',
            'name' => 'Stable Currency Basket',
            'type' => 'fixed',
            'rebalance_frequency' => 'never',
        ]);
        
        $this->basket->components()->createMany([
            ['asset_code' => 'USD', 'weight' => 40.0],
            ['asset_code' => 'EUR', 'weight' => 35.0],
            ['asset_code' => 'GBP', 'weight' => 25.0],
        ]);
        
        // Calculate basket value
        app(BasketValueCalculationService::class)->calculateValue($this->basket);
    }

    /** @test */
    public function it_can_decompose_basket_into_components()
    {
        // Give account some basket holdings
        $this->account->addBalance('STABLE_BASKET', 10000); // 100.00 basket units
        
        $result = $this->service->decomposeBasket($this->account, 'STABLE_BASKET', 5000); // 50.00 units
        
        $this->assertArrayHasKey('basket_code', $result);
        $this->assertArrayHasKey('basket_amount', $result);
        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('decomposed_at', $result);
        
        $this->assertEquals('STABLE_BASKET', $result['basket_code']);
        $this->assertEquals(5000, $result['basket_amount']);
        
        // Check component amounts (50% of basket)
        $this->assertEquals(2000, $result['components']['USD']); // 40% of 5000
        $this->assertEquals(1750, $result['components']['EUR']); // 35% of 5000
        $this->assertEquals(1250, $result['components']['GBP']); // 25% of 5000
        
        // Check balances were updated
        $this->assertEquals(5000, $this->account->getBalance('STABLE_BASKET')); // 50.00 remaining
        $this->assertEquals(2000, $this->account->getBalance('USD'));
        $this->assertEquals(1750, $this->account->getBalance('EUR'));
        $this->assertEquals(1250, $this->account->getBalance('GBP'));
    }

    /** @test */
    public function it_cannot_decompose_with_insufficient_balance()
    {
        $this->account->addBalance('STABLE_BASKET', 1000); // 10.00 basket units
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient basket balance');
        
        $this->service->decomposeBasket($this->account, 'STABLE_BASKET', 2000); // 20.00 units
    }

    /** @test */
    public function it_can_compose_basket_from_components()
    {
        // Give account component balances
        $this->account->addBalance('USD', 4000);  // 40.00 USD
        $this->account->addBalance('EUR', 3500);  // 35.00 EUR
        $this->account->addBalance('GBP', 2500);  // 25.00 GBP
        
        $result = $this->service->composeBasket($this->account, 'STABLE_BASKET', 5000); // 50.00 units
        
        $this->assertArrayHasKey('basket_code', $result);
        $this->assertArrayHasKey('basket_amount', $result);
        $this->assertArrayHasKey('components_used', $result);
        $this->assertArrayHasKey('composed_at', $result);
        
        $this->assertEquals('STABLE_BASKET', $result['basket_code']);
        $this->assertEquals(5000, $result['basket_amount']);
        
        // Check components used
        $this->assertEquals(2000, $result['components_used']['USD']); // 40% of 5000
        $this->assertEquals(1750, $result['components_used']['EUR']); // 35% of 5000
        $this->assertEquals(1250, $result['components_used']['GBP']); // 25% of 5000
        
        // Check balances were updated
        $this->assertEquals(5000, $this->account->getBalance('STABLE_BASKET'));
        $this->assertEquals(2000, $this->account->getBalance('USD')); // 4000 - 2000
        $this->assertEquals(1750, $this->account->getBalance('EUR')); // 3500 - 1750
        $this->assertEquals(1250, $this->account->getBalance('GBP')); // 2500 - 1250
    }

    /** @test */
    public function it_cannot_compose_with_insufficient_components()
    {
        // Give account insufficient component balances
        $this->account->addBalance('USD', 1000);  // 10.00 USD (need 20.00)
        $this->account->addBalance('EUR', 3500);  // 35.00 EUR
        $this->account->addBalance('GBP', 2500);  // 25.00 GBP
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance for component USD');
        
        $this->service->composeBasket($this->account, 'STABLE_BASKET', 5000);
    }

    /** @test */
    public function it_can_get_basket_holdings_value()
    {
        // Give account various basket holdings
        $this->account->addBalance('STABLE_BASKET', 10000); // 100.00 units
        
        // Create another basket
        $cryptoBasket = BasketAsset::create([
            'code' => 'CRYPTO_BASKET',
            'name' => 'Crypto Basket',
            'type' => 'fixed',
            'rebalance_frequency' => 'never',
        ]);
        
        Asset::factory()->create(['code' => 'CRYPTO_BASKET', 'name' => 'Crypto Basket', 'type' => 'basket']);
        
        $cryptoBasket->components()->create([
            'asset_code' => 'USD',
            'weight' => 100.0,
        ]);
        
        $basketValue = app(BasketValueCalculationService::class)->calculateValue($cryptoBasket);
        
        $this->account->addBalance('CRYPTO_BASKET', 5000); // 50.00 units
        
        $holdings = $this->service->getBasketHoldingsValue($this->account);
        
        $this->assertArrayHasKey('account_uuid', $holdings);
        $this->assertArrayHasKey('basket_holdings', $holdings);
        $this->assertArrayHasKey('total_value', $holdings);
        $this->assertArrayHasKey('currency', $holdings);
        
        $this->assertEquals($this->account->uuid, $holdings['account_uuid']);
        $this->assertEquals('USD', $holdings['currency']);
        $this->assertCount(2, $holdings['basket_holdings']);
        
        // Check individual basket holdings
        $stableHolding = collect($holdings['basket_holdings'])->firstWhere('basket_code', 'STABLE_BASKET');
        $this->assertEquals(10000, $stableHolding['balance']);
        $this->assertGreaterThan(0, $stableHolding['unit_value']);
        $this->assertGreaterThan(0, $stableHolding['total_value']);
    }

    /** @test */
    public function it_only_decomposes_active_components()
    {
        // Deactivate EUR component
        $this->basket->components()->where('asset_code', 'EUR')->update(['is_active' => false]);
        
        $this->account->addBalance('STABLE_BASKET', 10000);
        
        $result = $this->service->decomposeBasket($this->account, 'STABLE_BASKET', 5000);
        
        // EUR should not be in components
        $this->assertArrayNotHasKey('EUR', $result['components']);
        $this->assertArrayHasKey('USD', $result['components']);
        $this->assertArrayHasKey('GBP', $result['components']);
        
        // Check no EUR balance was created
        $this->assertEquals(0, $this->account->getBalance('EUR'));
    }

    /** @test */
    public function it_handles_basket_not_found()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Basket not found: INVALID_BASKET');
        
        $this->service->decomposeBasket($this->account, 'INVALID_BASKET', 1000);
    }

    /** @test */
    public function it_handles_inactive_basket()
    {
        $this->basket->update(['is_active' => false]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Basket is not active: STABLE_BASKET');
        
        $this->service->decomposeBasket($this->account, 'STABLE_BASKET', 1000);
    }

    /** @test */
    public function it_validates_positive_amounts()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Amount must be positive');
        
        $this->service->decomposeBasket($this->account, 'STABLE_BASKET', -1000);
    }

    /** @test */
    public function it_handles_no_basket_holdings()
    {
        $holdings = $this->service->getBasketHoldingsValue($this->account);
        
        $this->assertEmpty($holdings['basket_holdings']);
        $this->assertEquals(0, $holdings['total_value']);
    }

    /** @test */
    public function it_calculates_required_components()
    {
        $required = $this->service->calculateRequiredComponents('STABLE_BASKET', 10000);
        
        $this->assertArrayHasKey('USD', $required);
        $this->assertArrayHasKey('EUR', $required);
        $this->assertArrayHasKey('GBP', $required);
        
        $this->assertEquals(4000, $required['USD']); // 40% of 10000
        $this->assertEquals(3500, $required['EUR']); // 35% of 10000
        $this->assertEquals(2500, $required['GBP']); // 25% of 10000
    }
}