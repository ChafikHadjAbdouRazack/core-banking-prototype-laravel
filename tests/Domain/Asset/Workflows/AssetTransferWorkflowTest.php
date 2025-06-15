<?php

declare(strict_types=1);

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Models\ExchangeRate;
use App\Domain\Asset\Workflows\AssetTransferWorkflow;
use App\Domain\Asset\Workflows\Activities\InitiateAssetTransferActivity;
use App\Domain\Asset\Workflows\Activities\ValidateExchangeRateActivity;
use App\Domain\Asset\Workflows\Activities\CompleteAssetTransferActivity;
use App\Models\Account;
use App\Models\AccountBalance;
use Workflow\WorkflowStub;

beforeEach(function () {
    // Assets are already seeded in migrations, no need to create duplicates
});

it('can execute same asset transfer workflow', function () {
    WorkflowStub::fake();
    
    $fromAccount = Account::factory()->create();
    $toAccount = Account::factory()->create();
    
    // Create USD balance for source account
    AccountBalance::factory()
        ->forAccount($fromAccount)
        ->forAsset('USD')
        ->withBalance(10000)
        ->create();
    
    $workflow = WorkflowStub::make(AssetTransferWorkflow::class);
    $workflow->start(
        new AccountUuid($fromAccount->uuid),
        new AccountUuid($toAccount->uuid),
        'USD',
        'USD',
        new Money(5000),
        'Test same asset transfer'
    );
    
    WorkflowStub::assertDispatched(InitiateAssetTransferActivity::class);
    WorkflowStub::assertDispatched(CompleteAssetTransferActivity::class);
    WorkflowStub::assertNotDispatched(ValidateExchangeRateActivity::class);
});

it('can execute cross asset transfer workflow', function () {
    WorkflowStub::fake();
    
    $fromAccount = Account::factory()->create();
    $toAccount = Account::factory()->create();
    
    // Create USD balance for source account
    AccountBalance::factory()
        ->forAccount($fromAccount)
        ->forAsset('USD')
        ->withBalance(10000)
        ->create();
    
    // Create exchange rate
    ExchangeRate::factory()
        ->between('USD', 'EUR')
        ->valid()
        ->create(['rate' => 0.85]);
    
    $workflow = WorkflowStub::make(AssetTransferWorkflow::class);
    $workflow->start(
        new AccountUuid($fromAccount->uuid),
        new AccountUuid($toAccount->uuid),
        'USD',
        'EUR',
        new Money(10000),
        'Test cross asset transfer'
    );
    
    WorkflowStub::assertDispatched(InitiateAssetTransferActivity::class);
    WorkflowStub::assertDispatched(ValidateExchangeRateActivity::class);
    WorkflowStub::assertDispatched(CompleteAssetTransferActivity::class);
});

it('handles workflow failures gracefully', function () {
    WorkflowStub::fake();
    
    $fromAccount = Account::factory()->create();
    $toAccount = Account::factory()->create();
    
    // Don't create sufficient balance - this should cause failure
    AccountBalance::factory()
        ->forAccount($fromAccount)
        ->forAsset('USD')
        ->withBalance(100) // Insufficient for 5000 transfer
        ->create();
    
    $workflow = WorkflowStub::make(AssetTransferWorkflow::class);
    
    expect(fn() => $workflow->start(
        new AccountUuid($fromAccount->uuid),
        new AccountUuid($toAccount->uuid),
        'USD',
        'USD',
        new Money(5000),
        'Test insufficient balance'
    ))->toThrow(\Exception::class);
});