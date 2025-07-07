<?php

use App\Providers\WaterlineServiceProvider;

it('extends WaterlineApplicationServiceProvider', function () {
    $reflection = new ReflectionClass(WaterlineServiceProvider::class);
    expect($reflection->getParentClass()->getName())->toBe('Waterline\WaterlineApplicationServiceProvider');
});

it('has gate method', function () {
    expect(method_exists(WaterlineServiceProvider::class, 'gate'))->toBeTrue();
});

it('gate method is protected', function () {
    $reflection = new ReflectionMethod(WaterlineServiceProvider::class, 'gate');
    expect($reflection->isProtected())->toBeTrue();
});

it('has correct class structure', function () {
    $reflection = new ReflectionClass(WaterlineServiceProvider::class);
    expect($reflection->isAbstract())->toBeFalse();
    expect($reflection->isFinal())->toBeFalse();
});
