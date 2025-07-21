<?php

declare(strict_types=1);

use App\Domain\Account\Services\AccountService;
use App\Http\Controllers\Api\AccountBalanceController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\ExchangeRateController;
use App\Models\Account;
use App\Models\User;

it('can instantiate all main API controllers', function () {
    $accountController = app(AccountController::class);
    $balanceController = app(AccountBalanceController::class);
    $assetController = app(AssetController::class);
    $exchangeController = app(ExchangeRateController::class);

    expect($accountController)->toBeInstanceOf(AccountController::class);
    expect($balanceController)->toBeInstanceOf(AccountBalanceController::class);
    expect($assetController)->toBeInstanceOf(AssetController::class);
    expect($exchangeController)->toBeInstanceOf(ExchangeRateController::class);
});

it('can instantiate domain services', function () {
    $accountService = app(AccountService::class);

    expect($accountService)->toBeInstanceOf(AccountService::class);
});

it('can create basic models and check relationships', function () {
    $user = User::factory()->create();
    $account = Account::factory()->forUser($user)->create();

    expect($account->user)->toBeInstanceOf(User::class);
    expect($account->user->id)->toBe($user->id);
});

it('can access account balance methods', function () {
    $account = Account::factory()->create();

    // Create account balance properly
    App\Domain\Account\Models\AccountBalance::factory()->create([
        'account_uuid' => $account->uuid,
        'asset_code'   => 'USD',
        'balance'      => 1000,
    ]);

    expect($account->getBalance())->toBe(1000);
    expect($account->balance)->toBe(1000);
});

it('can test enum methods', function () {
    $defaultQueue = App\Values\EventQueues::default();

    expect($defaultQueue)->toBe(App\Values\EventQueues::EVENTS);
    expect($defaultQueue->value)->toBe('events');
});

it('can test basic workflow instantiation', function () {
    // Just test that workflow classes can be instantiated without errors
    expect(function () {
        $workflow = new App\Domain\Account\Workflows\CreateAccountWorkflow();
        expect($workflow)->toBeInstanceOf(App\Domain\Account\Workflows\CreateAccountWorkflow::class);
    })->not->toThrow(Exception::class);
});

it('can test basic activity instantiation', function () {
    expect(function () {
        $activity = new App\Domain\Account\Workflows\CreateAccountActivity();
        expect($activity)->toBeInstanceOf(App\Domain\Account\Workflows\CreateAccountActivity::class);
    })->not->toThrow(Exception::class);
});

it('can test services cache methods', function () {
    $accountCacheService = app(App\Domain\Account\Services\Cache\AccountCacheService::class);
    $turnoverCacheService = app(App\Domain\Account\Services\Cache\TurnoverCacheService::class);

    expect($accountCacheService)->toBeInstanceOf(App\Domain\Account\Services\Cache\AccountCacheService::class);
    expect($turnoverCacheService)->toBeInstanceOf(App\Domain\Account\Services\Cache\TurnoverCacheService::class);
});

it('can access view components', function () {
    $appLayout = new App\View\Components\AppLayout();
    $guestLayout = new App\View\Components\GuestLayout();

    expect($appLayout)->toBeInstanceOf(App\View\Components\AppLayout::class);
    expect($guestLayout)->toBeInstanceOf(App\View\Components\GuestLayout::class);

    expect($appLayout->render())->toBeInstanceOf(Illuminate\Contracts\View\View::class);
    expect($guestLayout->render())->toBeInstanceOf(Illuminate\Contracts\View\View::class);
});
