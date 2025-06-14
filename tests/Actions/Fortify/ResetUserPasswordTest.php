<?php

use App\Actions\Fortify\ResetUserPassword;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

it('implements ResetsUserPasswords contract', function () {
    expect(ResetUserPassword::class)->toImplement(ResetsUserPasswords::class);
});

it('has reset method', function () {
    expect(method_exists(ResetUserPassword::class, 'reset'))->toBeTrue();
});

it('can be instantiated', function () {
    expect(new ResetUserPassword())->toBeInstanceOf(ResetUserPassword::class);
});

it('has correct method signature', function () {
    $reflection = new ReflectionMethod(ResetUserPassword::class, 'reset');
    expect($reflection->isPublic())->toBeTrue();
    expect($reflection->getNumberOfParameters())->toBe(2);
});