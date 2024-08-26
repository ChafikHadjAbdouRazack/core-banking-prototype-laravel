<?php

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\Facades\Schema;

test('creates event sourcing tables for customers', function () {
    // Mock the Schema facade
    Schema::shouldReceive('hasTable')->andReturn(false);
    Schema::shouldReceive('create')->times(3)->withArgs(function ($tableName, $closure) {
        return preg_match('/accounts_|snapshots_|transactions_/', $tableName) && is_callable($closure);
    });

    // Create an instance of CreateNewUser
    $action = new CreateNewUser();

    // Use Reflection to access the protected method
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('createEventTables');
    $method->setAccessible(true);

    // Call the method and pass the fake UUID
    $method->invoke($action, 'fake-uuid');
});
