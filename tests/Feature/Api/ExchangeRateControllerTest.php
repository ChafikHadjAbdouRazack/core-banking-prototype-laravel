<?php

use App\Models\User;
use App\Domain\Asset\Models\Asset;
use App\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    
    // Create test assets
    $this->usd = Asset::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]
    );
    
    $this->eur = Asset::firstOrCreate(
        ['code' => 'EUR'],
        ['name' => 'Euro', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]
    );
    
    $this->btc = Asset::firstOrCreate(
        ['code' => 'BTC'],
        ['name' => 'Bitcoin', 'type' => 'crypto', 'precision' => 8, 'is_active' => true]
    );
});

describe('Exchange Rate API - Public Endpoints', function () {
    
    test('can list all exchange rates', function () {
        // Create test exchange rates
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'is_active' => true
        ]);
        
        ExchangeRate::factory()->create([
            'from_asset_code' => 'BTC',
            'to_asset_code' => 'USD',
            'rate' => 45000.00,
            'source' => 'test',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/exchange-rates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'from_asset_code',
                        'to_asset_code',
                        'rate',
                        'source',
                        'updated_at'
                    ]
                ]
            ]);

        expect($response->json('data'))->toHaveCount(2);
    });

    test('can get specific exchange rate', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/exchange-rates/USD/EUR');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'from_asset_code',
                'to_asset_code',
                'rate',
                'source',
                'updated_at',
                'expires_at'
            ]);

        expect($response->json('from_asset_code'))->toBe('USD');
        expect($response->json('to_asset_code'))->toBe('EUR');
        expect($response->json('rate'))->toBe(0.85);
    });

    test('returns 404 for non-existent exchange rate', function () {
        $response = $this->getJson('/api/exchange-rates/USD/XYZ');

        $response->assertStatus(404);
    });

    test('can convert currency amounts', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/exchange-rates/USD/EUR/convert?amount=100');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'from_asset_code',
                'to_asset_code',
                'from_amount',
                'to_amount',
                'rate',
                'converted_at'
            ]);

        expect($response->json('from_amount'))->toBe(100.0);
        expect($response->json('to_amount'))->toBe(85.0);
    });

    test('validates conversion amount parameter', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/exchange-rates/USD/EUR/convert?amount=invalid');

        $response->assertStatus(422);
    });

    test('handles missing conversion amount', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/exchange-rates/USD/EUR/convert');

        $response->assertStatus(422);
    });

    test('filters rates by source', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'bank_a',
            'is_active' => true
        ]);
        
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.84,
            'source' => 'bank_b',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/exchange-rates?source=bank_a');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.source'))->toBe('bank_a');
    });

    test('filters rates by asset type', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'is_active' => true
        ]);
        
        ExchangeRate::factory()->create([
            'from_asset_code' => 'BTC',
            'to_asset_code' => 'USD',
            'rate' => 45000.00,
            'source' => 'test',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/exchange-rates?from_type=fiat');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.from_asset_code'))->toBe('USD');
    });

    test('returns only active rates by default', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'is_active' => true
        ]);
        
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'GBP',
            'rate' => 0.75,
            'source' => 'test',
            'is_active' => false
        ]);

        $response = $this->getJson('/api/exchange-rates');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.to_asset_code'))->toBe('EUR');
    });

    test('can include inactive rates when specified', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'is_active' => true
        ]);
        
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'GBP',
            'rate' => 0.75,
            'source' => 'test',
            'is_active' => false
        ]);

        $response = $this->getJson('/api/exchange-rates?include_inactive=true');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(2);
    });
});

describe('Exchange Rate API - Protected Endpoints', function () {
    
    beforeEach(function () {
        Sanctum::actingAs($this->user, ['*']);
    });

    test('can convert currency with authentication', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'is_active' => true
        ]);

        $response = $this->postJson('/api/exchange/convert', [
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'amount' => 1000
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'from_asset_code',
                'to_asset_code',
                'from_amount',
                'to_amount',
                'rate',
                'spread',
                'fee',
                'converted_at',
                'expires_at'
            ]);

        expect($response->json('from_amount'))->toBe(1000.0);
        expect($response->json('to_amount'))->toBeGreaterThan(800.0); // After spread/fees
    });

    test('validates conversion request', function () {
        $response = $this->postJson('/api/exchange/convert', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_asset_code', 'to_asset_code', 'amount']);
    });

    test('handles invalid asset codes in conversion', function () {
        $response = $this->postJson('/api/exchange/convert', [
            'from_asset_code' => 'INVALID',
            'to_asset_code' => 'ALSO_INVALID',
            'amount' => 100
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Exchange rate not available']);
    });

    test('handles zero amount conversion', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'is_active' => true
        ]);

        $response = $this->postJson('/api/exchange/convert', [
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'amount' => 0
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    test('handles negative amount conversion', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'is_active' => true
        ]);

        $response = $this->postJson('/api/exchange/convert', [
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'amount' => -100
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    test('requires authentication for conversion endpoint', function () {
        $response = $this->withHeaders(['Authorization' => 'Bearer invalid-token'])
            ->postJson('/api/exchange/convert', [
                'from_asset_code' => 'USD',
                'to_asset_code' => 'EUR',
                'amount' => 100
            ]);

        $response->assertStatus(401);
    });

    test('applies spread and fees correctly', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 1.0, // 1:1 for easy calculation
            'source' => 'test',
            'is_active' => true
        ]);

        $response = $this->postJson('/api/exchange/convert', [
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'amount' => 1000
        ]);

        $response->assertStatus(200);
        
        // Should be less than 1000 due to spread/fees
        expect($response->json('to_amount'))->toBeLessThan(1000.0);
        expect($response->json('spread'))->toBeGreaterThan(0);
        expect($response->json('fee'))->toBeGreaterThan(0);
    });

    test('handles large amount conversions', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'is_active' => true
        ]);

        $response = $this->postJson('/api/exchange/convert', [
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'amount' => 1000000 // 1 million
        ]);

        $response->assertStatus(200);
        expect($response->json('from_amount'))->toBe(1000000.0);
    });

    test('includes rate expiration information', function () {
        ExchangeRate::factory()->create([
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'is_active' => true
        ]);

        $response = $this->postJson('/api/exchange/convert', [
            'from_asset_code' => 'USD',
            'to_asset_code' => 'EUR',
            'amount' => 100
        ]);

        $response->assertStatus(200);
        expect($response->json('expires_at'))->not->toBeNull();
        expect($response->json('converted_at'))->not->toBeNull();
    });
});