<?php

use App\Actions\Jetstream\AddTeamMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

uses(RefreshDatabase::class);

it('can be instantiated', function () {
    expect(new AddTeamMember())->toBeInstanceOf(AddTeamMember::class);
});

it('implements AddsTeamMembers contract', function () {
    expect(AddTeamMember::class)->toImplement(Laravel\Jetstream\Contracts\AddsTeamMembers::class);
});

it('has validate method', function () {
    expect(method_exists(AddTeamMember::class, 'add'))->toBeTrue();
});

it('has rules method', function () {
    $reflection = new ReflectionClass(AddTeamMember::class);
    expect($reflection->hasMethod('rules'))->toBeTrue();
});
