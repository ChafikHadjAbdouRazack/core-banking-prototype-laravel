<?php

declare(strict_types=1);

use App\Domain\Account\Services\Cache\AccountCacheService;
use App\Models\Account;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('caches account data', function () {
    $account = Account::factory()->create();
    $cacheService = app(AccountCacheService::class);
    
    // First call should hit the database
    $cachedAccount = $cacheService->get($account->uuid);
    
    expect($cachedAccount)->toBeInstanceOf(Account::class);
    expect($cachedAccount->uuid)->toBe($account->uuid);
    
    // Delete the account from database
    $account->delete();
    
    // Second call should return from cache
    $cachedAccount2 = $cacheService->get($account->uuid);
    
    expect($cachedAccount2)->toBeInstanceOf(Account::class);
    expect($cachedAccount2->uuid)->toBe($account->uuid);
});

it('caches balance separately with shorter TTL', function () {
    $account = Account::factory()->withBalance(5000)->create();
    $cacheService = app(AccountCacheService::class);
    
    // Cache the balance
    $balance = $cacheService->getBalance($account->uuid);
    
    expect($balance)->toBe(5000);
    
    // Update account balance in database
    $account->update(['balance' => 10000]);
    
    // Should still return cached balance
    $cachedBalance = $cacheService->getBalance($account->uuid);
    
    expect($cachedBalance)->toBe(5000);
});

it('updates balance cache', function () {
    $account = Account::factory()->withBalance(5000)->create();
    $cacheService = app(AccountCacheService::class);
    
    // Cache the balance
    $cacheService->getBalance($account->uuid);
    
    // Update balance in cache
    $cacheService->updateBalance($account->uuid, 10000);
    
    // Should return updated balance
    $balance = $cacheService->getBalance($account->uuid);
    
    expect($balance)->toBe(10000);
});

it('forgets cached account', function () {
    $account = Account::factory()->create();
    $cacheService = app(AccountCacheService::class);
    
    // Cache the account
    $cacheService->get($account->uuid);
    
    // Delete from database
    $account->delete();
    
    // Forget from cache
    $cacheService->forget($account->uuid);
    
    // Should return null
    $result = $cacheService->get($account->uuid);
    
    expect($result)->toBeNull();
});

it('returns null for non-existent account', function () {
    $cacheService = app(AccountCacheService::class);
    $fakeUuid = \Illuminate\Support\Str::uuid()->toString();
    
    $result = $cacheService->get($fakeUuid);
    
    expect($result)->toBeNull();
});