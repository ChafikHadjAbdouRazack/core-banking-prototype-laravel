<?php

use App\Domain\Account\Services\Cache\CacheManager;

it('is a class', function () {
    expect(CacheManager::class)->toBeClass();
});

it('has constructor dependency injection', function () {
    $reflection = new ReflectionClass(CacheManager::class);
    $constructor = $reflection->getConstructor();
    
    expect($constructor->getNumberOfParameters())->toBe(3);
});

it('has required event handler methods', function () {
    expect(method_exists(CacheManager::class, 'onAccountUpdated'))->toBeTrue();
    expect(method_exists(CacheManager::class, 'onAccountDeleted'))->toBeTrue();
    expect(method_exists(CacheManager::class, 'onTransactionCreated'))->toBeTrue();
    expect(method_exists(CacheManager::class, 'onTurnoverCreated'))->toBeTrue();
    expect(method_exists(CacheManager::class, 'flushAll'))->toBeTrue();
    expect(method_exists(CacheManager::class, 'warmUp'))->toBeTrue();
});

it('has all method signatures with correct return types', function () {
    $reflection = new ReflectionClass(CacheManager::class);
    
    // Check all void methods
    foreach (['onAccountUpdated', 'onAccountDeleted', 'onTransactionCreated', 'onTurnoverCreated', 'flushAll', 'warmUp'] as $methodName) {
        $method = $reflection->getMethod($methodName);
        expect($method->getReturnType()?->getName())->toBe('void');
    }
});

it('constructor promotes dependencies correctly', function () {
    $reflection = new ReflectionClass(CacheManager::class);
    $constructor = $reflection->getConstructor();
    $params = $constructor->getParameters();
    
    expect($params[0]->isPromoted())->toBeTrue();
    expect($params[1]->isPromoted())->toBeTrue();
    expect($params[2]->isPromoted())->toBeTrue();
});