<?php

declare(strict_types=1);

use App\Domain\Asset\Models\Asset;
use App\Models\Account;
use App\Models\AccountBalance;

it('can list all assets', function () {
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
                    'metadata',
                ],
            ],
            'meta' => [
                'total',
                'active',
                'types' => [
                    'fiat',
                    'crypto',
                    'commodity',
                ],
            ],
        ]);

    // We should have seeded assets
    expect($response->json('data'))->toBeArray();
    expect(count($response->json('data')))->toBeGreaterThan(0);
});

it('can filter assets by type', function () {
    $response = $this->getJson('/api/v1/assets?type=fiat');

    $response->assertStatus(200);
    expect($response->json('data'))->toBeArray();
    
    // If there are results, they should all be fiat
    if (count($response->json('data')) > 0) {
        foreach ($response->json('data') as $asset) {
            expect($asset['type'])->toBe('fiat');
        }
    }
});

it('can filter assets by active status', function () {
    $response = $this->getJson('/api/v1/assets?active=true');

    $response->assertStatus(200);
    expect($response->json('data'))->toBeArray();
    
    // If there are results, they should all be active
    if (count($response->json('data')) > 0) {
        foreach ($response->json('data') as $asset) {
            expect($asset['is_active'])->toBeTrue();
        }
    }
});

it('can search assets by code or name', function () {
    // Search for USD which should exist in seeded data
    $response = $this->getJson('/api/v1/assets?search=USD');

    $response->assertStatus(200);
    expect($response->json('data'))->toBeArray();
});

it('can get asset details', function () {
    // Use USD which should exist in seeded data
    $response = $this->getJson('/api/v1/assets/USD');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'code',
                'name',
                'type',
                'symbol',
                'precision',
                'is_active',
                'metadata',
                'stats' => [
                    'total_accounts',
                    'total_balance',
                    'active_rates',
                ],
            ],
        ]);

    expect($response->json('data.code'))->toBe('USD');
});

it('returns 404 for non-existent asset', function () {
    $response = $this->getJson('/api/v1/assets/UNKNOWN');

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Asset not found',
            'error' => 'The specified asset code was not found',
        ]);
});