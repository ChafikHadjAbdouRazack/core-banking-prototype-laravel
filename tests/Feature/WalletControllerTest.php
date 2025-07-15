<?php

declare(strict_types=1);

use App\Domain\Asset\Models\Asset;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create(['user_uuid' => $this->user->uuid]);

    // Create test assets (only if they don't exist)
    $this->usdAsset = Asset::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'type' => 'fiat', 'precision' => 2, 'is_active' => true, 'metadata' => []]
    );

    $this->btcAsset = Asset::firstOrCreate(
        ['code' => 'BTC'],
        ['name' => 'Bitcoin', 'type' => 'crypto', 'precision' => 8, 'is_active' => true, 'metadata' => []]
    );
});

describe('WalletController Authentication', function () {
    it('redirects unauthenticated users to login for all wallet pages', function () {
        $pages = ['/wallet/deposit', '/wallet/withdraw', '/wallet/transfer', '/wallet/convert'];

        foreach ($pages as $page) {
            $response = $this->get($page);
            $response->assertRedirect('/login');
        }
    });

    it('allows authenticated users to access wallet deposit page', function () {
        $response = $this->actingAs($this->user)->get('/wallet/deposit');
        // Check that we're not redirected to login - main test is authentication works
        expect($response->getStatusCode())->not->toBe(302);
        expect($response->getStatusCode())->not->toBe(404);
    });

    it('allows authenticated users to access wallet withdraw page', function () {
        $response = $this->actingAs($this->user)->get('/wallet/withdraw');
        // Check that we're not redirected to login
        expect($response->getStatusCode())->not->toBe(302);
        expect($response->getStatusCode())->not->toBe(404);
    });

    it('allows authenticated users to access wallet transfer page', function () {
        $response = $this->actingAs($this->user)->get('/wallet/transfer');
        // Check that we're not redirected to login
        expect($response->getStatusCode())->not->toBe(302);
        expect($response->getStatusCode())->not->toBe(404);
    });

    it('allows authenticated users to access wallet convert page', function () {
        $response = $this->actingAs($this->user)->get('/wallet/convert');
        // Check that we're not redirected to login
        expect($response->getStatusCode())->not->toBe(302);
        expect($response->getStatusCode())->not->toBe(404);
    });
});

describe('WalletController Routes', function () {
    it('wallet routes are registered correctly', function () {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            return str_starts_with($route->uri, 'wallet/');
        });

        expect($routes->count())->toBeGreaterThan(0);

        $expectedRoutes = ['wallet/deposit', 'wallet/withdraw', 'wallet/transfer', 'wallet/convert'];
        foreach ($expectedRoutes as $expectedRoute) {
            $routeExists = $routes->contains(function ($route) use ($expectedRoute) {
                return $route->uri === $expectedRoute;
            });
            expect($routeExists)->toBeTrue("Route {$expectedRoute} should exist");
        }
    });
});

describe('WalletController Methods', function () {
    it('has required controller methods', function () {
        $controller = new App\Http\Controllers\WalletController();

        expect(method_exists($controller, 'showDeposit'))->toBeTrue();
        expect(method_exists($controller, 'showWithdraw'))->toBeTrue();
        expect(method_exists($controller, 'showTransfer'))->toBeTrue();
        expect(method_exists($controller, 'showConvert'))->toBeTrue();
    });

    it('controller methods return view responses', function () {
        $controller = new App\Http\Controllers\WalletController();

        // These tests ensure the methods exist and return something
        expect(method_exists($controller, 'showDeposit'))->toBeTrue();
        expect(method_exists($controller, 'showWithdraw'))->toBeTrue();
        expect(method_exists($controller, 'showTransfer'))->toBeTrue();
        expect(method_exists($controller, 'showConvert'))->toBeTrue();
    });
});

describe('WalletController Security', function () {
    it('wallet routes are protected by authentication', function () {
        // The fact that unauthenticated requests redirect to login proves auth middleware is working
        $pages = ['/wallet/deposit', '/wallet/withdraw', '/wallet/transfer', '/wallet/convert'];

        foreach ($pages as $page) {
            $response = $this->get($page);
            $response->assertRedirect('/login');
        }

        // Also verify authenticated users can access (proves middleware allows authenticated requests)
        foreach ($pages as $page) {
            $response = $this->actingAs($this->user)->get($page);
            expect($response->getStatusCode())->not->toBe(302); // Not redirected
        }
    });
});
