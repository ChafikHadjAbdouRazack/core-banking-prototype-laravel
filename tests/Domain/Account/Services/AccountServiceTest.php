<?php

use App\Domain\Account\Services\AccountService;
use App\Domain\Account\Workflows\CreateAccountWorkflow;
use App\Domain\Account\Workflows\DepositAccountWorkflow;
use App\Domain\Account\Workflows\DestroyAccountWorkflow;
use App\Domain\Account\Workflows\WithdrawAccountWorkflow;
use Workflow\WorkflowStub;

beforeEach(function () {
    WorkflowStub::fake();
    $this->accountService = app(AccountService::class);
});

it('has create method', function () {
    expect(method_exists($this->accountService, 'create'))->toBeTrue();
});

it('has destroy method', function () {
    expect(method_exists($this->accountService, 'destroy'))->toBeTrue();
});

it('has deposit method', function () {
    expect(method_exists($this->accountService, 'deposit'))->toBeTrue();
});

it('has withdraw method', function () {
    expect(method_exists($this->accountService, 'withdraw'))->toBeTrue();
});

it('can be instantiated from container', function () {
    expect($this->accountService)->toBeInstanceOf(AccountService::class);
});

it('can create account workflow with array data', function () {
    $accountData = [
        'name' => 'Test Account',
        'user_uuid' => 'user-uuid-123'
    ];

    $this->accountService->create($accountData);

    WorkflowStub::assertDispatched(CreateAccountWorkflow::class);
});

it('can destroy account workflow with uuid string', function () {
    $uuid = 'test-account-uuid';

    $this->accountService->destroy($uuid);

    WorkflowStub::assertDispatched(DestroyAccountWorkflow::class);
});

it('can deposit workflow with uuid and amount', function () {
    $uuid = 'test-account-uuid';
    $amount = 5000;

    $this->accountService->deposit($uuid, $amount);

    WorkflowStub::assertDispatched(DepositAccountWorkflow::class);
});

it('can withdraw workflow with uuid and amount', function () {
    $uuid = 'test-account-uuid';
    $amount = 2500;

    $this->accountService->withdraw($uuid, $amount);

    WorkflowStub::assertDispatched(WithdrawAccountWorkflow::class);
});