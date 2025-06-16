<?php

declare(strict_types=1);

use App\Domain\Asset\Models\Asset;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

// Assets are already seeded in migrations

it('can get account balances', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $account = Account::factory()->zeroBalance()->create();
    
    // Create balances for different assets
    AccountBalance::factory()->create([
        'account_uuid' => $account->uuid,
        'asset_code' => 'USD',
        'balance' => 150000, // $1500.00
    ]);
    
    AccountBalance::factory()->create([
        'account_uuid' => $account->uuid,
        'asset_code' => 'BTC',
        'balance' => 5000000, // 0.05 BTC
    ]);

    $response = $this->getJson("/api/accounts/{$account->uuid}/balances");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'account_uuid',
                'balances' => [
                    '*' => [
                        'asset_code',
                        'balance',
                        'formatted',
                        'asset' => [
                            'code',
                            'name',
                            'type',
                            'symbol',
                            'precision',
                        ],
                    ],
                ],
                'summary' => [
                    'total_assets',
                    'total_usd_equivalent',
                ],
            ],
        ]);

    expect($response->json('data.account_uuid'))->toBe((string) $account->uuid);
    expect($response->json('data.balances'))->toHaveCount(2);
    expect($response->json('data.summary.total_assets'))->toBe(2);
});

it('can filter account balances by asset', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $account = Account::factory()->zeroBalance()->create();
    
    AccountBalance::factory()->create([
        'account_uuid' => $account->uuid,
        'asset_code' => 'USD',
        'balance' => 100000,
    ]);
    
    AccountBalance::factory()->create([
        'account_uuid' => $account->uuid,
        'asset_code' => 'EUR',
        'balance' => 50000,
    ]);

    $response = $this->getJson("/api/accounts/{$account->uuid}/balances?asset=USD");

    $response->assertStatus(200);
    expect($response->json('data.balances'))->toHaveCount(1);
    expect($response->json('data.balances.0.asset_code'))->toBe('USD');
});

it('can filter account balances to positive only', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $account = Account::factory()->zeroBalance()->create();
    
    AccountBalance::factory()->create([
        'account_uuid' => $account->uuid,
        'asset_code' => 'USD',
        'balance' => 100000,
    ]);
    
    AccountBalance::factory()->create([
        'account_uuid' => $account->uuid,
        'asset_code' => 'EUR',
        'balance' => 0,
    ]);

    $response = $this->getJson("/api/accounts/{$account->uuid}/balances?positive=true");

    $response->assertStatus(200);
    expect($response->json('data.balances'))->toHaveCount(1);
    expect($response->json('data.balances.0.balance'))->toBeGreaterThan(0);
});

it('returns 404 for non-existent account', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/accounts/non-existent-uuid/balances');

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Account not found',
            'error' => 'The specified account UUID was not found',
        ]);
});

it('can list all account balances', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $account1 = Account::factory()->zeroBalance()->create();
    $account2 = Account::factory()->zeroBalance()->create();
    
    AccountBalance::factory()->create([
        'account_uuid' => $account1->uuid,
        'asset_code' => 'USD',
        'balance' => 100000,
    ]);
    
    AccountBalance::factory()->create([
        'account_uuid' => $account2->uuid,
        'asset_code' => 'EUR',
        'balance' => 50000,
    ]);

    $response = $this->getJson('/api/balances');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'account_uuid',
                    'asset_code',
                    'balance',
                    'formatted',
                    'account' => [
                        'uuid',
                        'user_uuid',
                    ],
                ],
            ],
            'meta' => [
                'total_accounts',
                'total_balances',
                'asset_totals',
            ],
        ]);

    expect($response->json('data'))->toHaveCount(2);
});

it('can filter all balances by asset', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $account = Account::factory()->zeroBalance()->create();
    
    AccountBalance::factory()->create([
        'account_uuid' => $account->uuid,
        'asset_code' => 'USD',
        'balance' => 100000,
    ]);
    
    AccountBalance::factory()->create([
        'account_uuid' => $account->uuid,
        'asset_code' => 'EUR',
        'balance' => 50000,
    ]);

    $response = $this->getJson('/api/balances?asset=USD');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.asset_code'))->toBe('USD');
});

it('can filter all balances by minimum balance', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $account = Account::factory()->zeroBalance()->create();
    
    AccountBalance::factory()->create([
        'account_uuid' => $account->uuid,
        'asset_code' => 'USD',
        'balance' => 100000,
    ]);
    
    AccountBalance::factory()->create([
        'account_uuid' => $account->uuid,
        'asset_code' => 'EUR',
        'balance' => 500,
    ]);

    $response = $this->getJson('/api/balances?min_balance=10000');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.balance'))->toBeGreaterThanOrEqual(10000);
});

it('can limit number of balance results', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $account1 = Account::factory()->zeroBalance()->create();
    $account2 = Account::factory()->zeroBalance()->create();
    $account3 = Account::factory()->zeroBalance()->create();
    $account4 = Account::factory()->zeroBalance()->create();
    $account5 = Account::factory()->zeroBalance()->create();
    
    // Create multiple balances with different asset codes to avoid conflicts
    AccountBalance::factory()->create([
        'account_uuid' => $account1->uuid,
        'asset_code' => 'USD',
        'balance' => 10000,
    ]);
    
    AccountBalance::factory()->create([
        'account_uuid' => $account2->uuid,
        'asset_code' => 'EUR',
        'balance' => 20000,
    ]);
    
    AccountBalance::factory()->create([
        'account_uuid' => $account3->uuid,
        'asset_code' => 'GBP',
        'balance' => 30000,
    ]);
    
    AccountBalance::factory()->create([
        'account_uuid' => $account4->uuid,
        'asset_code' => 'BTC',
        'balance' => 40000,
    ]);
    
    AccountBalance::factory()->create([
        'account_uuid' => $account5->uuid,
        'asset_code' => 'ETH',
        'balance' => 50000,
    ]);

    $response = $this->getJson('/api/balances?limit=3');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(3);
});

it('requires authentication for all endpoints', function () {
    $account = Account::factory()->create();

    $this->getJson("/api/accounts/{$account->uuid}/balances")
        ->assertStatus(401);

    $this->getJson('/api/balances')
        ->assertStatus(401);
});