<?php

declare(strict_types=1);

use App\Domain\Exchange\Providers\MockExchangeRateProvider;
use App\Domain\Exchange\Services\ExchangeRateProviderRegistry;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    
    // Register mock provider
    $registry = app(ExchangeRateProviderRegistry::class);
    $mockProvider = new MockExchangeRateProvider([
        'name' => 'Mock Exchange Provider',
        'priority' => 50,
    ]);
    $registry->register('mock', $mockProvider);
});

it('can list exchange rate providers', function () {
    $response = $this->getJson('/api/v1/exchange-providers');
    
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'name',
                    'display_name',
                    'available',
                    'priority',
                    'capabilities',
                    'supported_currencies',
                ],
            ],
            'default',
        ])
        ->assertJsonPath('data.0.name', 'mock')
        ->assertJsonPath('data.0.display_name', 'Mock Exchange Provider')
        ->assertJsonPath('data.0.available', true)
        ->assertJsonPath('data.0.priority', 50);
});

it('can get rate from specific provider', function () {
    $response = $this->getJson('/api/v1/exchange-providers/mock/rate?' . http_build_query([
        'from' => 'USD',
        'to' => 'EUR',
    ]));
    
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'from_currency',
                'to_currency',
                'rate',
                'bid',
                'ask',
                'spread',
                'spread_percentage',
                'provider',
                'timestamp',
                'age_seconds',
                'volume_24h',
                'change_24h',
                'metadata',
            ],
        ])
        ->assertJsonPath('data.from_currency', 'USD')
        ->assertJsonPath('data.to_currency', 'EUR')
        ->assertJsonPath('data.provider', 'Mock Exchange Provider');
});

it('returns error for non-existent provider', function () {
    $response = $this->getJson('/api/v1/exchange-providers/invalid/rate?' . http_build_query([
        'from' => 'USD',
        'to' => 'EUR',
    ]));
    
    $response->assertBadRequest()
        ->assertJsonPath('error', 'Failed to get exchange rate');
});

it('validates rate request parameters', function () {
    $response = $this->getJson('/api/v1/exchange-providers/mock/rate');
    
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['from', 'to']);
});

it('can compare rates from all providers', function () {
    // Add a second provider
    $registry = app(ExchangeRateProviderRegistry::class);
    $secondProvider = new MockExchangeRateProvider([
        'name' => 'Second Provider',
        'priority' => 30,
    ]);
    $registry->register('second', $secondProvider);
    
    $response = $this->getJson('/api/v1/exchange-providers/compare?' . http_build_query([
        'from' => 'USD',
        'to' => 'EUR',
    ]));
    
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'mock' => [
                    'rate',
                    'bid',
                    'ask',
                    'spread',
                    'spread_percentage',
                    'timestamp',
                    'age_seconds',
                ],
                'second' => [
                    'rate',
                    'bid',
                    'ask',
                ],
            ],
            'pair',
            'timestamp',
        ])
        ->assertJsonPath('pair', 'USD/EUR');
});

it('can get aggregated rate', function () {
    // Add a second provider
    $registry = app(ExchangeRateProviderRegistry::class);
    $secondProvider = new MockExchangeRateProvider(['name' => 'Second Provider']);
    $registry->register('second', $secondProvider);
    
    $response = $this->getJson('/api/v1/exchange-providers/aggregated?' . http_build_query([
        'from' => 'USD',
        'to' => 'EUR',
    ]));
    
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'from_currency',
                'to_currency',
                'rate',
                'bid',
                'ask',
                'provider',
                'metadata',
            ],
        ])
        ->assertJsonPath('data.provider', 'aggregated')
        ->assertJsonPath('data.metadata.count', 2);
});

it('can refresh exchange rates with authentication', function () {
    Sanctum::actingAs($this->user);
    
    $response = $this->postJson('/api/v1/exchange-providers/refresh', [
        'pairs' => ['USD/EUR', 'BTC/USD'],
    ]);
    
    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'data' => [
                'refreshed',
                'failed',
            ],
        ])
        ->assertJsonPath('message', 'Exchange rates refreshed');
});

it('requires authentication for refresh endpoint', function () {
    $response = $this->postJson('/api/v1/exchange-providers/refresh');
    
    $response->assertUnauthorized();
});

it('can get historical rates', function () {
    // Create some exchange rate records
    \App\Domain\Asset\Models\ExchangeRate::factory()->count(5)->create([
        'from_asset_code' => 'USD',
        'to_asset_code' => 'EUR',
        'created_at' => now()->subDays(1),
    ]);
    
    $response = $this->getJson('/api/v1/exchange-providers/historical?' . http_build_query([
        'from' => 'USD',
        'to' => 'EUR',
        'start_date' => now()->subDays(2)->toDateString(),
        'end_date' => now()->toDateString(),
    ]));
    
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'rate',
                    'bid',
                    'ask',
                    'source',
                    'timestamp',
                ],
            ],
            'pair',
            'period',
        ])
        ->assertJsonPath('pair', 'USD/EUR');
});

it('can validate exchange rate', function () {
    $response = $this->postJson('/api/v1/exchange-providers/validate', [
        'from' => 'USD',
        'to' => 'EUR',
        'rate' => 0.85,
        'provider' => 'test',
    ]);
    
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'valid',
                'warnings',
            ],
        ]);
});

it('validates currency pair format in refresh', function () {
    Sanctum::actingAs($this->user);
    
    $response = $this->postJson('/api/v1/exchange-providers/refresh', [
        'pairs' => ['INVALID_FORMAT', 'USD/EUR'],
    ]);
    
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['pairs.0']);
});