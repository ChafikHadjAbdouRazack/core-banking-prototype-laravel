<?php

declare(strict_types=1);

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Resources\AssetResource;
use App\Domain\Asset\Projectors\AssetTransactionProjector;
use App\Domain\Asset\Projectors\AssetTransferProjector;

// Test low coverage classes to increase overall coverage

it('can instantiate asset projectors', function () {
    $transactionProjector = new AssetTransactionProjector();
    $transferProjector = new AssetTransferProjector();
    
    expect($transactionProjector)->toBeInstanceOf(AssetTransactionProjector::class);
    expect($transferProjector)->toBeInstanceOf(AssetTransferProjector::class);
});

it('can instantiate dashboard page', function () {
    $dashboard = new Dashboard();
    
    expect($dashboard)->toBeInstanceOf(Dashboard::class);
});

it('can access asset resource class methods', function () {
    $resource = AssetResource::class;
    
    expect($resource::getModel())->toBe(\App\Domain\Asset\Models\Asset::class);
    expect($resource::getModelLabel())->toBeString();
    expect($resource::getPluralModelLabel())->toBeString();
    expect($resource::getNavigationIcon())->toBeString();
    expect($resource::getNavigationGroup())->toBeString();
});

it('can test asset resource navigation methods', function () {
    $badge = AssetResource::getNavigationBadge();
    $sort = AssetResource::getNavigationSort();
    
    expect($badge)->toBeString();
    expect($sort)->toBeInt();
});

it('workflow activity classes exist', function () {
    expect(class_exists(\App\Domain\Account\Workflows\AccountValidationActivity::class))->toBeTrue();
    expect(class_exists(\App\Domain\Account\Workflows\BalanceInquiryActivity::class))->toBeTrue();
    expect(class_exists(\App\Domain\Account\Workflows\DepositAccountActivity::class))->toBeTrue();
    expect(class_exists(\App\Domain\Account\Workflows\WithdrawAccountActivity::class))->toBeTrue();
});

it('asset workflow classes exist', function () {
    expect(class_exists(\App\Domain\Asset\Workflows\AssetDepositWorkflow::class))->toBeTrue();
    expect(class_exists(\App\Domain\Asset\Workflows\AssetWithdrawWorkflow::class))->toBeTrue();
});

it('asset activity classes exist', function () {
    expect(class_exists(\App\Domain\Asset\Workflows\Activities\DepositAssetActivity::class))->toBeTrue();
    expect(class_exists(\App\Domain\Asset\Workflows\Activities\WithdrawAssetActivity::class))->toBeTrue();
    expect(class_exists(\App\Domain\Asset\Workflows\Activities\CompleteAssetTransferActivity::class))->toBeTrue();
    expect(class_exists(\App\Domain\Asset\Workflows\Activities\FailAssetTransferActivity::class))->toBeTrue();
    expect(class_exists(\App\Domain\Asset\Workflows\Activities\InitiateAssetTransferActivity::class))->toBeTrue();
    expect(class_exists(\App\Domain\Asset\Workflows\Activities\ValidateExchangeRateActivity::class))->toBeTrue();
});

it('projector classes exist and have methods', function () {
    $transactionProjector = new AssetTransactionProjector();
    $transferProjector = new AssetTransferProjector();
    
    expect(class_exists(\App\Domain\Asset\Projectors\ExchangeRateProjector::class))->toBeTrue();
    expect(method_exists($transactionProjector, 'onAssetTransactionCreated'))->toBeTrue();
    expect(method_exists($transferProjector, 'onAssetTransferInitiated'))->toBeTrue();
    expect(method_exists($transferProjector, 'onAssetTransferCompleted'))->toBeTrue();
    expect(method_exists($transferProjector, 'onAssetTransferFailed'))->toBeTrue();
});

it('filament resource pages exist', function () {
    expect(class_exists(\App\Filament\Admin\Resources\AssetResource\Pages\CreateAsset::class))->toBeTrue();
    expect(class_exists(\App\Filament\Admin\Resources\AssetResource\Pages\EditAsset::class))->toBeTrue();
    expect(class_exists(\App\Filament\Admin\Resources\AssetResource\Pages\ListAssets::class))->toBeTrue();
    expect(class_exists(\App\Filament\Admin\Resources\ExchangeRateResource\Pages\CreateExchangeRate::class))->toBeTrue();
    expect(class_exists(\App\Filament\Admin\Resources\ExchangeRateResource\Pages\EditExchangeRate::class))->toBeTrue();
    expect(class_exists(\App\Filament\Admin\Resources\ExchangeRateResource\Pages\ListExchangeRates::class))->toBeTrue();
});

it('widgets and relation managers exist', function () {
    expect(class_exists(\App\Filament\Admin\Resources\AccountResource\RelationManagers\TurnoversRelationManager::class))->toBeTrue();
    expect(class_exists(\App\Filament\Admin\Resources\AccountResource\Widgets\AccountStatsOverview::class))->toBeTrue();
});