<?php

use App\Actions\Fortify\CreateNewUser;
use Laravel\Fortify\Contracts\CreatesNewUsers;

it('implements CreatesNewUsers contract', function () {
    expect(CreateNewUser::class)->toImplement(CreatesNewUsers::class);
});

it('has create method', function () {
    expect(method_exists(CreateNewUser::class, 'create'))->toBeTrue();
});

it('can be instantiated', function () {
    expect(new CreateNewUser())->toBeInstanceOf(CreateNewUser::class);
});

it('has correct method signature', function () {
    $reflection = new ReflectionMethod(CreateNewUser::class, 'create');
    expect($reflection->isPublic())->toBeTrue();
    expect($reflection->getNumberOfParameters())->toBe(1);
});
