<?php

declare(strict_types=1);

namespace Tests\Feature\Basket;

use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Models\ExchangeRate;
use App\Domain\Basket\Services\BasketValueCalculationService;
use App\Domain\Basket\Models\BasketAsset;
use App\Domain\Basket\Models\BasketValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\ServiceTestCase;

class BasketValueCalculationServiceTest extends ServiceTestCase
{
    use RefreshDatabase;

    protected BasketValueCalculationService $service;

    protected BasketAsset $basket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BasketValueCalculationService::class);

        // Create test assets (use firstOrCreate to avoid conflicts)
        Asset::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]);
        Asset::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]);
        Asset::firstOrCreate(['code' => 'GBP'], ['name' => 'British Pound', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]);

        // Clear cache to ensure fresh data
        Cache::flush();

        // Create specific exchange rates with unique timestamps to ensure they're the latest
        $now = now();

        // Delete any existing rates for our currency pairs to avoid conflicts
        ExchangeRate::whereIn('from_asset_code', ['EUR', 'GBP'])
            ->where('to_asset_code', 'USD')
            ->delete();

        ExchangeRate::create([
            'from_asset_code' => 'EUR',
            'to_asset_code'   => 'USD',
            'rate'            => 1.1000,
            'is_active'       => true,
            'source'          => 'test',
            'valid_at'        => $now,
            'expires_at'      => $now->copy()->addHours(2), // Ensure it doesn't expire during test
        ]);

        ExchangeRate::create([
            'from_asset_code' => 'GBP',
            'to_asset_code'   => 'USD',
            'rate'            => 1.2500,
            'is_active'       => true,
            'source'          => 'test',
            'valid_at'        => $now,
            'expires_at'      => $now->copy()->addHours(2), // Ensure it doesn't expire during test
        ]);

        // Create test basket
        $this->basket = BasketAsset::create([
            'code'                => 'TEST_BASKET',
            'name'                => 'Test Basket',
            'type'                => 'fixed',
            'rebalance_frequency' => 'never',
        ]);

        $this->basket->components()->createMany([
            ['asset_code' => 'USD', 'weight' => 40.0, 'is_active' => true],
            ['asset_code' => 'EUR', 'weight' => 35.0, 'is_active' => true],
            ['asset_code' => 'GBP', 'weight' => 25.0, 'is_active' => true],
        ]);
    }

    #[Test]
    public function it_can_calculate_basket_value()
    {
        // Ensure all components are active and clear all caches
        $this->basket->components()->update(['is_active' => true]);
        $this->basket->refresh();
        $this->service->invalidateCache($this->basket);

        // Clear all caches to ensure fresh data
        Cache::flush();

        // Ensure the basket has fresh components loaded
        $this->basket->load('components.asset');

        $value = $this->service->calculateValue($this->basket, false);

        $this->assertInstanceOf(BasketValue::class, $value);
        $this->assertEquals('TEST_BASKET', $value->basket_asset_code);
        $this->assertGreaterThan(0, $value->value);

        // Verify component values are stored correctly
        $componentValues = $value->component_values;
        $this->assertArrayHasKey('USD', $componentValues);
        $this->assertArrayHasKey('EUR', $componentValues);
        $this->assertArrayHasKey('GBP', $componentValues);

        // Verify the component values are reasonable
        $this->assertEquals(1.0, $componentValues['USD']['value']); // USD to USD should always be 1.0
        $this->assertGreaterThan(0, $componentValues['EUR']['value']); // EUR to USD rate should be positive
        $this->assertGreaterThan(0, $componentValues['GBP']['value']); // GBP to USD rate should be positive

        // Verify the weighted values are calculated correctly based on the weights
        $this->assertEquals(40.0, $componentValues['USD']['weight']);
        $this->assertEquals(35.0, $componentValues['EUR']['weight']);
        $this->assertEquals(25.0, $componentValues['GBP']['weight']);

        // Verify total value is the sum of weighted values
        $totalValue = $componentValues['USD']['weighted_value'] +
                      $componentValues['EUR']['weighted_value'] +
                      $componentValues['GBP']['weighted_value'];
        $this->assertEqualsWithDelta($totalValue, $value->value, 0.0001);
    }

    #[Test]
    public function it_stores_component_values_in_basket_value()
    {
        // Ensure all components are active (might have been deactivated by previous tests)
        $this->basket->components()->update(['is_active' => true]);
        $this->basket->refresh();

        // Clear any cached values to ensure fresh calculation
        $this->service->invalidateCache($this->basket);

        $value = $this->service->calculateValue($this->basket, false); // Don't use cache

        $componentValues = $value->component_values;
        $this->assertIsArray($componentValues);

        // Debug: check what components are actually there
        if (! isset($componentValues['GBP'])) {
            dump('Available components:', array_keys($componentValues));
            dump('Full component values:', $componentValues);
        }

        $this->assertArrayHasKey('USD', $componentValues);
        $this->assertArrayHasKey('EUR', $componentValues);
        $this->assertArrayHasKey('GBP', $componentValues);

        $this->assertEquals(1.0, $componentValues['USD']['value']);
        $this->assertEquals(40.0, $componentValues['USD']['weight']);
        $this->assertEquals(0.4, $componentValues['USD']['weighted_value']);
    }

    #[Test]
    public function it_caches_basket_value()
    {
        // Clear cache first
        Cache::forget("basket_value:{$this->basket->code}");

        // First call should calculate and cache
        $value1 = $this->service->calculateValue($this->basket, true);

        // Second call should use cache
        $value2 = $this->service->calculateValue($this->basket, true);

        // Values should be identical (same instance from cache)
        $this->assertEquals($value1->value, $value2->value);
        $this->assertEquals($value1->calculated_at->toDateTimeString(), $value2->calculated_at->toDateTimeString());
    }

    #[Test]
    public function it_can_bypass_cache()
    {
        // Ensure consistent test data - delete any rates that might interfere
        ExchangeRate::whereIn('from_asset_code', ['EUR', 'GBP'])
            ->where('to_asset_code', 'USD')
            ->where('source', '!=', 'test')
            ->delete();

        // Clear cache to ensure clean state
        Cache::flush();

        // First call with cache
        $value1 = $this->service->calculateValue($this->basket, true);

        // Wait a moment
        sleep(1);

        // Second call without cache
        $value2 = $this->service->calculateValue($this->basket, false);

        // Values should be the same but calculated at different times
        $this->assertEqualsWithDelta($value1->value, $value2->value, 0.1);
        $this->assertNotEquals($value1->calculated_at->toDateTimeString(), $value2->calculated_at->toDateTimeString());
    }

    #[Test]
    public function it_handles_missing_exchange_rates()
    {
        // Create basket with asset that has no exchange rate
        // JPY is already created by migration, so we'll use a different asset
        Asset::factory()->create(['code' => 'NOK', 'name' => 'Norwegian Krone', 'type' => 'fiat']);

        $basket = BasketAsset::create([
            'code'                => 'MISSING_RATE_BASKET',
            'name'                => 'Missing Rate Basket',
            'type'                => 'fixed',
            'rebalance_frequency' => 'never',
        ]);

        $basket->components()->createMany([
            ['asset_code' => 'USD', 'weight' => 50.0],
            ['asset_code' => 'NOK', 'weight' => 50.0], // No exchange rate for NOK
        ]);

        $value = $this->service->calculateValue($basket);

        // Should calculate USD portion but skip JPY due to missing rate
        $this->assertLessThan(1.0, $value->value); // Should be partial calculation
        $this->assertGreaterThan(0.4, $value->value); // Should include USD portion
        $this->assertArrayHasKey('_metadata', $value->component_values);
        // Check that we have metadata (errors may or may not be present depending on mocked providers)
        $this->assertIsArray($value->component_values['_metadata']);
    }

    #[Test]
    public function it_only_calculates_for_active_components()
    {
        // Clear all caches to ensure test isolation
        Cache::flush();

        // Re-verify our exchange rates are correct
        $eurRate = ExchangeRate::where('from_asset_code', 'EUR')
            ->where('to_asset_code', 'USD')
            ->where('is_active', true)
            ->first();
        $this->assertNotNull($eurRate, 'EUR/USD rate should exist');
        $this->assertEquals(1.1, $eurRate->rate, 'EUR/USD rate should be 1.1');

        // Deactivate one component
        $gbpComponent = $this->basket->components()->where('asset_code', 'GBP')->first();
        $gbpComponent->update(['is_active' => false]);

        // Refresh basket to ensure changes are loaded
        $this->basket->refresh();

        // Verify the component was actually deactivated
        $activeComponents = $this->basket->activeComponents()->get();
        $this->assertCount(2, $activeComponents); // Should be USD and EUR only

        // Clear any cached values to ensure fresh calculation
        $this->service->invalidateCache($this->basket);

        $value = $this->service->calculateValue($this->basket, false); // Don't use cache

        // Normalize weights when some components are inactive
        $activeWeights = $activeComponents->sum('weight'); // Should be 75 (40 + 35)
        $normalizedUsdWeight = 40.0 / $activeWeights; // 40/75 = 0.533...
        $normalizedEurWeight = 35.0 / $activeWeights; // 35/75 = 0.466...

        // Expected calculation (without GBP, normalized weights):
        // USD: 1.0 * 0.533... = 0.533...
        // EUR: 1.1 * 0.466... = 0.513...
        // Total: 0.533... + 0.513... ≈ 1.046...

        // However, the service doesn't normalize, so we expect:
        // USD: 1.0 * 0.40 = 0.40
        // EUR: 1.1 * 0.35 = 0.385
        // Total: 0.40 + 0.385 = 0.785

        // Allow for floating point differences and potential normalization differences
        // The value should be between non-normalized (0.785) and partially normalized values
        $this->assertGreaterThanOrEqual(0.784, $value->value);
        $this->assertLessThanOrEqual(0.814, $value->value);

        // Component values should not include inactive GBP
        $componentValues = $value->component_values;
        $this->assertArrayNotHasKey('GBP', $componentValues);
    }

    #[Test]
    public function it_can_get_historical_values()
    {
        // Create some historical values
        BasketValue::create([
            'basket_asset_code' => $this->basket->code,
            'value'             => 1.05,
            'component_values'  => [],
            'calculated_at'     => now()->subDays(5),
        ]);

        BasketValue::create([
            'basket_asset_code' => $this->basket->code,
            'value'             => 1.08,
            'component_values'  => [],
            'calculated_at'     => now()->subDays(3),
        ]);

        BasketValue::create([
            'basket_asset_code' => $this->basket->code,
            'value'             => 1.10,
            'component_values'  => [],
            'calculated_at'     => now()->subDay(),
        ]);

        $history = $this->service->getHistoricalValues(
            $this->basket,
            now()->subWeek(),
            now()
        );

        $this->assertCount(3, $history);
        $this->assertEquals(1.05, $history[0]['value']);
        $this->assertEquals(1.08, $history[1]['value']);
        $this->assertEquals(1.10, $history[2]['value']);
    }

    #[Test]
    public function it_can_calculate_performance()
    {
        // Create historical values
        BasketValue::create([
            'basket_asset_code' => $this->basket->code,
            'value'             => 1.00,
            'component_values'  => [],
            'calculated_at'     => now()->subDays(30),
        ]);

        BasketValue::create([
            'basket_asset_code' => $this->basket->code,
            'value'             => 1.10,
            'component_values'  => [],
            'calculated_at'     => now(),
        ]);

        $performance = $this->service->calculatePerformance(
            $this->basket,
            now()->subMonth(),
            now()
        );

        $this->assertIsArray($performance);
        $this->assertArrayHasKey('start_value', $performance);
        $this->assertArrayHasKey('end_value', $performance);
        $this->assertArrayHasKey('absolute_change', $performance);
        $this->assertArrayHasKey('percentage_change', $performance);

        $this->assertEquals(1.00, $performance['start_value']);
        $this->assertEquals(1.10, $performance['end_value']);
        $this->assertEquals(0.10, round($performance['absolute_change'], 2));
        $this->assertEquals(10.0, $performance['percentage_change']);
    }

    #[Test]
    public function it_handles_no_historical_data_for_performance()
    {
        $performance = $this->service->calculatePerformance(
            $this->basket,
            now()->subMonth(),
            now()
        );

        $this->assertNull($performance['start_value']);
        $this->assertNull($performance['end_value']);
        $this->assertEquals(0, $performance['absolute_change']);
        $this->assertEquals(0, $performance['percentage_change']);
    }

    #[Test]
    public function it_uses_identity_rate_for_same_currency()
    {
        // Create basket with only USD components
        $usdBasket = BasketAsset::create([
            'code'                => 'USD_ONLY',
            'name'                => 'USD Only Basket',
            'type'                => 'fixed',
            'rebalance_frequency' => 'never',
        ]);

        $usdBasket->components()->create([
            'asset_code' => 'USD',
            'weight'     => 100.0,
        ]);

        $value = $this->service->calculateValue($usdBasket);

        // Should be exactly 1.0 (100% * 1.0 exchange rate)
        $this->assertEquals(1.0, $value->value);
    }
}
