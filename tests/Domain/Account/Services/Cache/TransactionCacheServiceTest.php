<?php

use App\Domain\Account\Services\Cache\TransactionCacheService;
use Illuminate\Support\Facades\Cache;

it('can be instantiated', function () {
    $service = new TransactionCacheService();
    expect($service)->toBeInstanceOf(TransactionCacheService::class);
});

it('has required methods', function () {
    $methods = ['getRecent', 'getPaginated', 'get', 'getDailySummary', 'forget', 'put'];
    
    foreach ($methods as $method) {
        expect(method_exists(TransactionCacheService::class, $method))->toBeTrue();
    }
});

it('can generate cache keys', function () {
    $service = new TransactionCacheService();
    $reflection = new ReflectionClass($service);
    
    $method = $reflection->getMethod('getCacheKey');
    $method->setAccessible(true);
    
    $key = $method->invoke($service, 'test-account', 'recent_10');
    expect($key)->toBeString();
    expect($key)->toContain('transaction:');
    expect($key)->toContain('test-account');
});

it('can forget account cache', function () {
    Cache::shouldReceive('forget')->atLeast()->once();
    
    $service = new TransactionCacheService();
    $service->forget('test-account-uuid');
    
    expect(true)->toBeTrue();
});

it('can call get method', function () {
    Cache::shouldReceive('remember')->once()->andReturn(null);
    
    $service = new TransactionCacheService();
    $result = $service->get('test-transaction-uuid');
    
    expect($result)->toBeNull();
});