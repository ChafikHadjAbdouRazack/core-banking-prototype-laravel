<?php

declare(strict_types=1);

use App\Domain\Asset\Models\Asset;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssetControllerTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    public function test_can_list_assets()
    {
        Sanctum::actingAs($this->user);

        // Create test assets with unique codes
        Asset::factory()->create([
            'code' => 'USD_TEST',
            'name' => 'US Dollar',
            'type' => 'fiat',
            'is_active' => true,
        ]);

        Asset::factory()->create([
            'code' => 'EUR_TEST',
            'name' => 'Euro',
            'type' => 'fiat',
            'is_active' => true,
        ]);

        Asset::factory()->create([
            'code' => 'BTC_TEST',
            'name' => 'Bitcoin',
            'type' => 'crypto',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/assets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'code',
                        'name',
                        'type',
                        'precision',
                        'is_active',
                        'metadata',
                    ]
                ]
            ]);
        
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_can_filter_assets_by_active_status()
    {
        Sanctum::actingAs($this->user);

        Asset::factory()->create(['is_active' => true]);
        Asset::factory()->create(['is_active' => true]);
        Asset::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/assets?active=true');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        
        foreach ($response->json('data') as $asset) {
            $this->assertTrue($asset['is_active']);
        }
    }

    public function test_can_filter_assets_by_type()
    {
        Sanctum::actingAs($this->user);

        Asset::factory()->create(['type' => 'fiat']);
        Asset::factory()->create(['type' => 'fiat']);
        Asset::factory()->create(['type' => 'crypto']);

        $response = $this->getJson('/api/assets?type=fiat');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        
        foreach ($response->json('data') as $asset) {
            $this->assertEquals('fiat', $asset['type']);
        }
    }

    public function test_can_search_assets_by_code()
    {
        Sanctum::actingAs($this->user);

        Asset::factory()->create(['code' => 'USD', 'name' => 'US Dollar']);
        Asset::factory()->create(['code' => 'EUR', 'name' => 'Euro']);
        Asset::factory()->create(['code' => 'BTC', 'name' => 'Bitcoin']);

        $response = $this->getJson('/api/assets?search=USD');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('USD', $response->json('data.0.code'));
    }

    public function test_can_search_assets_by_name()
    {
        Sanctum::actingAs($this->user);

        Asset::factory()->create(['code' => 'USD', 'name' => 'US Dollar']);
        Asset::factory()->create(['code' => 'EUR', 'name' => 'Euro']);

        $response = $this->getJson('/api/assets?search=Dollar');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('US Dollar', $response->json('data.0.name'));
    }

    public function test_can_combine_filters()
    {
        Sanctum::actingAs($this->user);

        Asset::factory()->create(['type' => 'fiat', 'is_active' => true]);
        Asset::factory()->create(['type' => 'fiat', 'is_active' => false]);
        Asset::factory()->create(['type' => 'crypto', 'is_active' => true]);

        $response = $this->getJson('/api/assets?type=fiat&active=true');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        
        $asset = $response->json('data.0');
        $this->assertEquals('fiat', $asset['type']);
        $this->assertTrue($asset['is_active']);
    }

    public function test_can_show_specific_asset()
    {
        Sanctum::actingAs($this->user);

        $asset = Asset::factory()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'type' => 'fiat',
            'precision' => 2,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/assets/{$asset->code}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'code',
                    'name',
                    'type',
                    'precision',
                    'is_active',
                    'metadata',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'code' => 'USD',
                    'name' => 'US Dollar',
                    'type' => 'fiat',
                    'precision' => 2,
                    'is_active' => true,
                ]
            ]);
    }

    public function test_returns_404_for_nonexistent_asset()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/assets/NONEXISTENT');

        $response->assertStatus(404);
    }

    public function test_can_create_asset()
    {
        Sanctum::actingAs($this->user);

        $assetData = [
            'code' => 'GBP',
            'name' => 'British Pound',
            'type' => 'fiat',
            'precision' => 2,
            'is_active' => true,
            'metadata' => [
                'symbol' => '£',
                'country' => 'United Kingdom',
            ],
        ];

        $response = $this->postJson('/api/assets', $assetData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'code',
                    'name',
                    'type',
                    'precision',
                    'is_active',
                    'metadata',
                ],
                'message'
            ])
            ->assertJson([
                'data' => $assetData,
                'message' => 'Asset created successfully',
            ]);

        $this->assertDatabaseHas('assets', [
            'code' => 'GBP',
            'name' => 'British Pound',
            'type' => 'fiat',
        ]);
    }

    public function test_validates_required_fields_for_asset_creation()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/assets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name', 'type']);
    }

    public function test_validates_asset_type()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/assets', [
            'code' => 'TEST',
            'name' => 'Test Asset',
            'type' => 'invalid_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_prevents_duplicate_asset_codes()
    {
        Sanctum::actingAs($this->user);

        Asset::factory()->create(['code' => 'USD']);

        $response = $this->postJson('/api/assets', [
            'code' => 'USD',
            'name' => 'Another USD',
            'type' => 'fiat',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_can_update_asset()
    {
        Sanctum::actingAs($this->user);

        $asset = Asset::factory()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'is_active' => true,
        ]);

        $updateData = [
            'name' => 'United States Dollar',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/assets/{$asset->code}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'code' => 'USD',
                    'name' => 'United States Dollar',
                    'is_active' => false,
                ],
                'message' => 'Asset updated successfully',
            ]);

        $this->assertDatabaseHas('assets', [
            'code' => 'USD',
            'name' => 'United States Dollar',
            'is_active' => false,
        ]);
    }

    public function test_requires_authentication_for_all_endpoints()
    {
        Asset::factory()->create(['code' => 'USD']);

        // Test all endpoints require authentication
        $this->getJson('/api/assets')->assertStatus(401);
        $this->getJson('/api/assets/USD')->assertStatus(401);
        $this->postJson('/api/assets', [])->assertStatus(401);
        $this->putJson('/api/assets/USD', [])->assertStatus(401);
    }
}