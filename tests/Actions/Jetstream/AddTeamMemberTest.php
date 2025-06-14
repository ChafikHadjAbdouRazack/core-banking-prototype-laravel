<?php

use App\Actions\Jetstream\AddTeamMember;
use Illuminate\Support\Facades\Event;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Events\AddingTeamMember;
use Laravel\Jetstream\Events\TeamMemberAdded;

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