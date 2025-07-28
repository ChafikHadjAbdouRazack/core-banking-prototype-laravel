<?php

declare(strict_types=1);

use App\Domain\Account\Models\Account;
use App\Domain\Asset\Models\Asset;
use App\Models\User;
use App\Providers\AppServiceProvider;

// Test service provider classes for coverage
it('can instantiate service providers', function () {
    $appProvider = new AppServiceProvider(app());

    expect($appProvider)->toBeInstanceOf(AppServiceProvider::class);
});

// Test model relationships and methods
it('can test model relationships', function () {
    $user = User::factory()->create();
    $account = Account::factory()->forUser($user)->create();

    // Test relationships exist
    expect($account->user())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($account->balances())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);

    // Test model methods
    expect($account->uuid)->not->toBeNull();
    expect($account->name)->toBeString();
    expect($account->balance)->toBeInt();
    expect($user->uuid)->not->toBeNull();
    expect($user->name)->toBeString();
});

// Test asset model methods
it('can test asset model methods', function () {
    $asset = Asset::factory()->create([
        'code'      => 'TEST',
        'name'      => 'Test Asset',
        'type'      => 'fiat',
        'precision' => 2,
        'is_active' => true,
    ]);

    expect($asset->code)->toBe('TEST');
    expect($asset->name)->toBe('Test Asset');
    expect($asset->type)->toBe('fiat');
    expect($asset->precision)->toBe(2);
    expect($asset->is_active)->toBeTrue();
    expect($asset->isFiat())->toBeTrue();
    expect($asset->isCrypto())->toBeFalse();
    expect($asset->isCommodity())->toBeFalse();
});

// Test additional controller methods
it('can test controller class existence', function () {
    expect(class_exists(App\Http\Controllers\Controller::class))->toBeTrue();
    expect(class_exists(App\Http\Controllers\Api\AccountController::class))->toBeTrue();
    expect(class_exists(App\Http\Controllers\Api\AssetController::class))->toBeTrue();
    expect(class_exists(App\Http\Controllers\Api\ExchangeRateController::class))->toBeTrue();
    expect(class_exists(App\Http\Controllers\Api\AccountBalanceController::class))->toBeTrue();
    expect(class_exists(App\Http\Controllers\Api\TransferController::class))->toBeTrue();
});

// Test enum classes
it('can test enum values and methods', function () {
    $userRoles = App\Values\UserRoles::cases();
    $eventQueues = App\Values\EventQueues::cases();

    expect($userRoles)->toBeArray();
    expect($eventQueues)->toBeArray();
    expect(count($userRoles))->toBeGreaterThan(0);
    expect(count($eventQueues))->toBeGreaterThan(0);

    expect(App\Values\UserRoles::ADMIN->value)->toBe('admin');
    expect(App\Values\EventQueues::EVENTS->value)->toBe('events');
});

// Test factory classes
it('can test factory methods', function () {
    $userFactory = User::factory();
    $accountFactory = Account::factory();
    $assetFactory = Asset::factory();

    expect($userFactory)->toBeInstanceOf(Database\Factories\UserFactory::class);
    expect($accountFactory)->toBeInstanceOf(Database\Factories\AccountFactory::class);
    expect($assetFactory)->toBeInstanceOf(Database\Factories\AssetFactory::class);
});

// Test additional cache service methods
it('can test cache service methods', function () {
    $accountCacheService = app(App\Domain\Account\Services\Cache\AccountCacheService::class);
    $transactionCacheService = app(App\Domain\Account\Services\Cache\TransactionCacheService::class);
    $turnoverCacheService = app(App\Domain\Account\Services\Cache\TurnoverCacheService::class);

    expect($accountCacheService)->toBeInstanceOf(App\Domain\Account\Services\Cache\AccountCacheService::class);
    expect($transactionCacheService)->toBeInstanceOf(App\Domain\Account\Services\Cache\TransactionCacheService::class);
    expect($turnoverCacheService)->toBeInstanceOf(App\Domain\Account\Services\Cache\TurnoverCacheService::class);
});

// Test additional account service
it('can test account service', function () {
    $accountService = app(App\Domain\Account\Services\AccountService::class);

    expect($accountService)->toBeInstanceOf(App\Domain\Account\Services\AccountService::class);
});

// Test middleware class existence
it('can test middleware class existence', function () {
    expect(class_exists(Illuminate\Auth\Middleware\Authenticate::class))->toBeTrue();
    expect(class_exists(Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class))->toBeTrue();
    expect(class_exists(Illuminate\Foundation\Http\Middleware\TrimStrings::class))->toBeTrue();
});

// Test additional domain event classes
it('can test domain event class existence', function () {
    expect(class_exists(App\Domain\Account\Events\MoneyAdded::class))->toBeTrue();
    expect(class_exists(App\Domain\Account\Events\MoneySubtracted::class))->toBeTrue();
    expect(class_exists(App\Domain\Asset\Events\AssetTransactionCreated::class))->toBeTrue();
    expect(class_exists(App\Domain\Asset\Events\AssetTransferInitiated::class))->toBeTrue();
});
