<?php

use App\View\Components\AppLayout;

it('extends Component', function () {
    $reflection = new ReflectionClass(AppLayout::class);
    expect($reflection->getParentClass()->getName())->toBe('Illuminate\View\Component');
});

it('has render method', function () {
    expect(method_exists(AppLayout::class, 'render'))->toBeTrue();
});

it('render method returns View', function () {
    $reflection = new ReflectionClass(AppLayout::class);
    $method = $reflection->getMethod('render');

    expect($method->getReturnType()->getName())->toBe('Illuminate\View\View');
});

it('can be instantiated', function () {
    expect(new AppLayout)->toBeInstanceOf(AppLayout::class);
});

it('has correct class structure', function () {
    $reflection = new ReflectionClass(AppLayout::class);
    expect($reflection->isAbstract())->toBeFalse();
    expect($reflection->isFinal())->toBeFalse();
    expect($reflection->getNamespaceName())->toBe('App\View\Components');
});
