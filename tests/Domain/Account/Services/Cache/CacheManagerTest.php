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
});