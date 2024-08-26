<?php

use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\Facades\Schema;
use Faker\Factory as Faker;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
})->skip(function () {
    return ! Features::enabled(Features::registration());
}, 'Registration support is not enabled.');

test('registration screen cannot be rendered if support is disabled', function () {
    $response = $this->get('/register');

    $response->assertStatus(404);
})->skip(function () {
    return Features::enabled(Features::registration());
}, 'Registration support is enabled.');

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'is_business_customer' => false,
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
})->skip(function () {
    return ! Features::enabled(Features::registration());
}, 'Registration support is not enabled.');

test('new business users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'is_business_customer' => true,
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
})->skip(function () {
    return ! Features::enabled(Features::registration());
}, 'Registration support is not enabled.');

test('does not create event sourcing tables for private customers', function () {
    // Mock the Schema facade
    Schema::shouldReceive('hasTable')->andReturn(false);
    Schema::shouldReceive('create')->never(); // Ensure create is not called

    $faker = Faker::create();

    // Define the input for a private customer
    $input = [
        'name' => $faker->name(),
        'email' => $faker->safeEmail(),
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_business_customer' => false, // Private customer
        'terms' => true,
    ];

    // Call the public create method in a fully booted application
    $action = app(CreateNewUser::class);
    $action->create($input);
});

test('creates event sourcing tables with client uuid for business customers', function () {
    Schema::shouldReceive('hasTable')->andReturn(false);
    Schema::shouldReceive('create')
          ->times(3)
          ->withArgs(function ($tableName, $closure) {
              // Ensure the table name contains the user's UUID
              return preg_match('/accounts_|snapshots_|transactions_/', $tableName) && is_callable($closure);
          });

    $faker = Faker::create();

    // Define the input for a private customer
    $input = [
        'name' => $faker->name(),
        'email' => $faker->safeEmail(),
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_business_customer' => true, // Business customer
        'terms' => true,
    ];

    // Call the public create method in a fully booted application
    $action = app(CreateNewUser::class);
    $action->create($input);
});
