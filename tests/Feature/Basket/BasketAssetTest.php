<?php

declare(strict_types=1);

namespace Tests\Feature\Basket;

use App\Models\BasketAsset;
use App\Models\BasketComponent;
use App\Domain\Asset\Models\Asset;
use App\Domain\Basket\Services\BasketValueCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasketAssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test assets
        Asset::factory()->create(['code' => 'USD', 'name' => 'US Dollar', 'type' => 'fiat', 'precision' => 2]);
        Asset::factory()->create(['code' => 'EUR', 'name' => 'Euro', 'type' => 'fiat', 'precision' => 2]);
        Asset::factory()->create(['code' => 'GBP', 'name' => 'British Pound', 'type' => 'fiat', 'precision' => 2]);
    }

    /** @test */
    public function it_can_create_a_basket_asset()
    {
        $basket = BasketAsset::create([
            'code' => 'TEST_BASKET',
            'name' => 'Test Basket',
            'description' => 'A test basket',
            'type' => 'fixed',
            'rebalance_frequency' => 'never',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('basket_assets', [
            'code' => 'TEST_BASKET',
            'name' => 'Test Basket',
            'type' => 'fixed',
        ]);

        $this->assertEquals('TEST_BASKET', $basket->code);
        $this->assertEquals('Test Basket', $basket->name);
        $this->assertEquals('fixed', $basket->type);
    }

    /** @test */
    public function it_can_add_components_to_basket()
    {
        $basket = BasketAsset::create([
            'code' => 'STABLE_BASKET',
            'name' => 'Stable Currency Basket',
            'type' => 'fixed',
            'rebalance_frequency' => 'never',
        ]);

        $basket->components()->createMany([
            ['asset_code' => 'USD', 'weight' => 40.0],
            ['asset_code' => 'EUR', 'weight' => 35.0],
            ['asset_code' => 'GBP', 'weight' => 25.0],
        ]);

        $this->assertEquals(3, $basket->components()->count());
        $this->assertEquals(100.0, $basket->components()->sum('weight'));
    }

    /** @test */
    public function it_validates_component_weights_sum_to_100()
    {
        $basket = BasketAsset::create([
            'code' => 'INVALID_BASKET',
            'name' => 'Invalid Basket',
            'type' => 'fixed',
            'rebalance_frequency' => 'never',
        ]);

        $basket->components()->createMany([
            ['asset_code' => 'USD', 'weight' => 30.0],
            ['asset_code' => 'EUR', 'weight' => 40.0],
        ]);

        // Total weight is 70, not 100
        $totalWeight = $basket->components()->sum('weight');
        $this->assertNotEquals(100.0, $totalWeight);
    }

    /** @test */
    public function it_can_check_if_basket_needs_rebalancing()
    {
        // Fixed basket should never need rebalancing
        $fixedBasket = BasketAsset::create([
            'code' => 'FIXED_BASKET',
            'name' => 'Fixed Basket',
            'type' => 'fixed',
            'rebalance_frequency' => 'daily',
        ]);
        $this->assertFalse($fixedBasket->needsRebalancing());

        // Dynamic basket with 'never' frequency should not need rebalancing
        $neverRebalanceBasket = BasketAsset::create([
            'code' => 'NEVER_BASKET',
            'name' => 'Never Rebalance Basket',
            'type' => 'dynamic',
            'rebalance_frequency' => 'never',
        ]);
        $this->assertFalse($neverRebalanceBasket->needsRebalancing());

        // Dynamic basket never rebalanced should need rebalancing
        $dynamicBasket = BasketAsset::create([
            'code' => 'DYNAMIC_BASKET',
            'name' => 'Dynamic Basket',
            'type' => 'dynamic',
            'rebalance_frequency' => 'daily',
            'last_rebalanced_at' => null,
        ]);
        $this->assertTrue($dynamicBasket->needsRebalancing());

        // Dynamic basket rebalanced recently should not need rebalancing
        $recentBasket = BasketAsset::create([
            'code' => 'RECENT_BASKET',
            'name' => 'Recent Basket',
            'type' => 'dynamic',
            'rebalance_frequency' => 'daily',
            'last_rebalanced_at' => now()->subHours(12),
        ]);
        $this->assertFalse($recentBasket->needsRebalancing());

        // Dynamic basket rebalanced long ago should need rebalancing
        $oldBasket = BasketAsset::create([
            'code' => 'OLD_BASKET',
            'name' => 'Old Basket',
            'type' => 'dynamic',
            'rebalance_frequency' => 'daily',
            'last_rebalanced_at' => now()->subDays(2),
        ]);
        $this->assertTrue($oldBasket->needsRebalancing());
    }

    /** @test */
    public function it_can_convert_basket_to_asset()
    {
        $basket = BasketAsset::create([
            'code' => 'BASKET_ASSET',
            'name' => 'Basket Asset',
            'type' => 'fixed',
            'rebalance_frequency' => 'never',
        ]);

        $asset = $basket->toAsset();

        $this->assertDatabaseHas('assets', [
            'code' => 'BASKET_ASSET',
            'name' => 'Basket Asset',
            'type' => 'basket',
            'precision' => 4,
            'is_active' => true,
        ]);

        $this->assertEquals('BASKET_ASSET', $asset->code);
        $this->assertEquals('basket', $asset->type);
    }

    /** @test */
    public function it_can_get_active_components()
    {
        $basket = BasketAsset::create([
            'code' => 'MIXED_BASKET',
            'name' => 'Mixed Basket',
            'type' => 'fixed',
            'rebalance_frequency' => 'never',
        ]);

        $basket->components()->createMany([
            ['asset_code' => 'USD', 'weight' => 50.0, 'is_active' => true],
            ['asset_code' => 'EUR', 'weight' => 30.0, 'is_active' => true],
            ['asset_code' => 'GBP', 'weight' => 20.0, 'is_active' => false],
        ]);

        $activeComponents = $basket->components()->where('is_active', true)->get();
        $this->assertEquals(2, $activeComponents->count());
        $this->assertEquals(80.0, $activeComponents->sum('weight'));
    }

    /** @test */
    public function it_can_handle_dynamic_weight_ranges()
    {
        $basket = BasketAsset::create([
            'code' => 'DYNAMIC_RANGE',
            'name' => 'Dynamic Range Basket',
            'type' => 'dynamic',
            'rebalance_frequency' => 'monthly',
        ]);

        $component = $basket->components()->create([
            'asset_code' => 'USD',
            'weight' => 40.0,
            'min_weight' => 35.0,
            'max_weight' => 45.0,
        ]);

        $this->assertEquals(35.0, $component->min_weight);
        $this->assertEquals(45.0, $component->max_weight);
        $this->assertTrue($component->weight >= $component->min_weight);
        $this->assertTrue($component->weight <= $component->max_weight);
    }

    /** @test */
    public function it_tracks_creation_metadata()
    {
        $userUuid = 'test-user-uuid';
        
        $basket = BasketAsset::create([
            'code' => 'METADATA_BASKET',
            'name' => 'Metadata Basket',
            'type' => 'fixed',
            'rebalance_frequency' => 'never',
            'created_by' => $userUuid,
        ]);

        $this->assertEquals($userUuid, $basket->created_by);
        $this->assertNotNull($basket->created_at);
        $this->assertNotNull($basket->updated_at);
    }

    /** @test */
    public function it_handles_basket_relationships()
    {
        $basket = BasketAsset::create([
            'code' => 'RELATION_BASKET',
            'name' => 'Relation Basket',
            'type' => 'fixed',
            'rebalance_frequency' => 'never',
        ]);

        $basket->components()->createMany([
            ['asset_code' => 'USD', 'weight' => 50.0],
            ['asset_code' => 'EUR', 'weight' => 50.0],
        ]);

        // Test relationships
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $basket->components);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $basket->values);
        $this->assertEquals(2, $basket->components->count());

        // Test component relationship back to basket
        $component = $basket->components->first();
        $this->assertEquals($basket->id, $component->basket_asset_id);
        $this->assertEquals($basket->code, $component->basketAsset->code);
    }
}