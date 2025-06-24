<?php

use App\Models\User;
use App\Domain\Asset\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    
    // Create test assets using firstOrCreate to avoid unique constraint issues
    $this->btc = Asset::firstOrCreate(
        ['code' => 'BTC'],
        [
            'name' => 'Bitcoin',
            'type' => 'crypto',
            'precision' => 8,
            'is_active' => true,
            'metadata' => [
                'symbol' => '₿',
                'decimals' => 8,
                'contract_address' => null,
                'network' => 'bitcoin',
                'market_cap_rank' => 1
            ]
        ]
    );
    
    $this->eth = Asset::firstOrCreate(
        ['code' => 'ETH'],
        [
            'name' => 'Ethereum',
            'type' => 'crypto',
            'precision' => 18,
            'is_active' => true,
            'metadata' => [
                'symbol' => 'Ξ',
                'decimals' => 18,
                'contract_address' => null,
                'network' => 'ethereum',
                'market_cap_rank' => 2
            ]
        ]
    );
    
    $this->usd = Asset::firstOrCreate(
        ['code' => 'USD'],
        [
            'name' => 'US Dollar',
            'type' => 'fiat',
            'precision' => 2,
            'is_active' => true,
            'metadata' => [
                'symbol' => '$',
                'decimals' => 2,
                'iso_code' => 'USD',
                'country' => 'United States'
            ]
        ]
    );
    
    $this->inactiveAsset = Asset::firstOrCreate(
        ['code' => 'INACTIVE'],
        [
            'name' => 'Inactive Token',
            'type' => 'crypto',
            'is_active' => false
        ]
    );
});

describe('Asset API - Public Endpoints', function () {
    
    test('can list all active assets', function () {
        $response = $this->getJson('/api/v1/assets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'code',
                        'name',
                        'type',
                        'symbol',
                        'precision',
                        'is_active',
                        'metadata'
                    ]
                ],
                'meta' => [
                    'total',
                    'active',
                    'types'
                ]
            ]);

        // Should return only active assets by default
        $codes = collect($response->json('data'))->pluck('code')->toArray();
        expect($codes)->toContain('BTC', 'ETH', 'USD');
        expect($codes)->not->toContain('INACTIVE');
    });

    test('can list all assets including inactive when specified', function () {
        $response = $this->getJson('/api/v1/assets?include_inactive=true');

        $response->assertStatus(200);
        
        $codes = collect($response->json('data'))->pluck('code')->toArray();
        expect($codes)->toContain('BTC', 'ETH', 'USD', 'INACTIVE');
    });

    test('can filter assets by type', function () {
        $response = $this->getJson('/api/v1/assets?type=crypto');

        $response->assertStatus(200);
        
        $assets = collect($response->json('data'));
        expect($assets->every(fn($asset) => $asset['type'] === 'crypto'))->toBeTrue();
        expect($assets->pluck('code')->toArray())->toContain('BTC', 'ETH');
        expect($assets->pluck('code')->toArray())->not->toContain('USD');
    });

    test('can filter assets by multiple types', function () {
        $response = $this->getJson('/api/v1/assets?type=crypto,fiat');

        $response->assertStatus(200);
        
        $types = collect($response->json('data'))->pluck('type')->unique()->toArray();
        expect($types)->toContain('crypto', 'fiat');
    });

    test('can search assets by name or code', function () {
        $response = $this->getJson('/api/v1/assets?search=bitcoin');

        $response->assertStatus(200);
        
        $assets = collect($response->json('data'));
        expect($assets->where('code', 'BTC'))->toHaveCount(1);
    });

    test('can search assets by partial code match', function () {
        $response = $this->getJson('/api/v1/assets?search=BT');

        $response->assertStatus(200);
        
        $codes = collect($response->json('data'))->pluck('code')->toArray();
        expect($codes)->toContain('BTC');
    });

    test('can get specific asset details', function () {
        $response = $this->getJson('/api/v1/assets/BTC');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'code',
                'name',
                'type',
                'precision',
                'is_active',
                'metadata' => [
                    'symbol',
                    'decimals',
                    'network'
                ],
                'statistics' => [
                    'total_supply',
                    'circulating_supply',
                    'market_data'
                ],
                'created_at',
                'updated_at'
            ]);

        expect($response->json('code'))->toBe('BTC');
        expect($response->json('name'))->toBe('Bitcoin');
    });

    test('returns 404 for non-existent asset', function () {
        $response = $this->getJson('/api/v1/assets/NONEXISTENT');

        $response->assertStatus(404);
    });

    test('returns 404 for inactive asset by default', function () {
        $response = $this->getJson('/api/v1/assets/INACTIVE');

        $response->assertStatus(404);
    });

    test('can get inactive asset when explicitly requested', function () {
        $response = $this->getJson('/api/v1/assets/INACTIVE?include_inactive=true');

        $response->assertStatus(200);
        expect($response->json('code'))->toBe('INACTIVE');
        expect($response->json('is_active'))->toBeFalse();
    });

    test('handles pagination correctly', function () {
        // Create additional assets
        Asset::factory()->count(15)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/assets?per_page=5');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(5);
        expect($response->json('total'))->toBe(18); // 3 existing + 15 new
    });

    test('sorts assets by market cap rank when available', function () {
        $response = $this->getJson('/api/v1/assets?sort=market_cap');

        $response->assertStatus(200);
        
        $firstAsset = $response->json('data.0');
        expect($firstAsset['code'])->toBe('BTC'); // Rank 1
    });

    test('sorts assets alphabetically by default', function () {
        $response = $this->getJson('/api/v1/assets?sort=name');

        $response->assertStatus(200);
        
        $names = collect($response->json('data'))->pluck('name')->toArray();
        $sortedNames = collect($names)->sort()->values()->toArray();
        expect($names)->toBe($sortedNames);
    });

    test('validates asset type filter', function () {
        $response = $this->getJson('/api/v1/assets?type=invalid_type');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    });

    test('validates pagination parameters', function () {
        $response = $this->getJson('/api/v1/assets?per_page=101'); // Max 100

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    });

    test('returns asset with market data when available', function () {
        // Update asset with market data
        $this->btc->update([
            'metadata' => array_merge($this->btc->metadata, [
                'market_data' => [
                    'price_usd' => 45000.50,
                    'market_cap_usd' => 850000000000,
                    'volume_24h_usd' => 25000000000,
                    'price_change_24h' => 2.5,
                    'last_updated' => now()->toISOString()
                ]
            ])
        ]);

        $response = $this->getJson('/api/v1/assets/BTC');

        $response->assertStatus(200);
        expect($response->json('metadata.market_data.price_usd'))->toBe(45000.50);
        expect($response->json('metadata.market_data.price_change_24h'))->toBe(2.5);
    });

    test('includes supported networks for crypto assets', function () {
        // Update Ethereum with network information
        $this->eth->update([
            'metadata' => array_merge($this->eth->metadata, [
                'supported_networks' => ['ethereum', 'polygon', 'arbitrum'],
                'contract_addresses' => [
                    'ethereum' => null, // Native token
                    'polygon' => '0x7ceB23fD6bC0adD59E62ac25578270cFf1b9f619',
                    'arbitrum' => '0x82aF49447D8a07e3bd95BD0d56f35241523fBab1'
                ]
            ])
        ]);

        $response = $this->getJson('/api/v1/assets/ETH');

        $response->assertStatus(200);
        expect($response->json('metadata.supported_networks'))->toHaveCount(3);
        expect($response->json('metadata.contract_addresses'))->toHaveKey('ethereum');
    });

    test('returns asset statistics', function () {
        $response = $this->getJson('/api/v1/assets/BTC');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'statistics' => [
                    'total_supply',
                    'circulating_supply',
                    'market_data'
                ]
            ]);
    });

    test('handles asset search with special characters', function () {
        Asset::factory()->create([
            'code' => 'SPECIAL',
            'name' => 'Asset with special chars: $%#',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/assets?search=' . urlencode('special chars'));

        $response->assertStatus(200);
        expect(collect($response->json('data'))->pluck('code')->toArray())->toContain('SPECIAL');
    });

    test('returns empty results for non-matching search', function () {
        $response = $this->getJson('/api/v1/assets?search=nonexistentasset');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(0);
    });

    test('can combine multiple filters', function () {
        $response = $this->getJson('/api/v1/assets?type=crypto&search=bit&sort=name');

        $response->assertStatus(200);
        
        $assets = collect($response->json('data'));
        expect($assets->every(fn($asset) => $asset['type'] === 'crypto'))->toBeTrue();
        expect($assets->where('code', 'BTC'))->toHaveCount(1);
    });

    test('handles case insensitive search', function () {
        $response = $this->getJson('/api/v1/assets?search=BITCOIN');

        $response->assertStatus(200);
        expect(collect($response->json('data'))->pluck('code')->toArray())->toContain('BTC');
    });

    test('returns correct metadata structure for different asset types', function () {
        // Test crypto asset metadata
        $cryptoResponse = $this->getJson('/api/v1/assets/BTC');
        $cryptoResponse->assertStatus(200);
        expect($cryptoResponse->json('metadata'))->toHaveKeys(['symbol', 'decimals', 'network']);

        // Test fiat asset metadata
        $fiatResponse = $this->getJson('/api/v1/assets/USD');
        $fiatResponse->assertStatus(200);
        expect($fiatResponse->json('metadata'))->toHaveKeys(['symbol', 'iso_code', 'country']);
    });

    test('handles concurrent requests efficiently', function () {
        $responses = [];
        
        // Simulate concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->getJson('/api/v1/assets');
        }

        foreach ($responses as $response) {
            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(3); // BTC, ETH, USD
        }
    });

    test('returns consistent response format', function () {
        $listResponse = $this->getJson('/api/v1/assets');
        $detailResponse = $this->getJson('/api/v1/assets/BTC');

        $listResponse->assertStatus(200);
        $detailResponse->assertStatus(200);

        // Verify list item has same structure as detail (minus statistics)
        $listItem = collect($listResponse->json('data'))->firstWhere('code', 'BTC');
        $detailItem = $detailResponse->json();

        $sharedKeys = ['id', 'code', 'name', 'type', 'precision', 'is_active', 'metadata'];
        foreach ($sharedKeys as $key) {
            expect($listItem[$key])->toBe($detailItem[$key]);
        }
    });
});