<?php

use App\Providers\FortifyServiceProvider;
use Illuminate\Foundation\Application;

beforeEach(function () {
    $this->app = Mockery::mock(Application::class);
    $this->provider = new FortifyServiceProvider($this->app);
});

it('can instantiate fortify service provider', function () {
    expect($this->provider)->toBeInstanceOf(FortifyServiceProvider::class);
});

it('has register method that can be called', function () {
    // Test that register method exists and can be called without errors
    expect(function () {
        $this->provider->register();
    })->not->toThrow(Exception::class);
});

it('has boot method', function () {
    // Just test that the boot method exists
    expect(method_exists($this->provider, 'boot'))->toBeTrue();
});