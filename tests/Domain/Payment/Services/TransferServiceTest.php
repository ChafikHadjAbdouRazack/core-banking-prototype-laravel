<?php

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Payment\Services\TransferService;
use App\Domain\Payment\Workflows\TransferWorkflow;
use Workflow\WorkflowStub;

beforeEach(function () {
    WorkflowStub::fake();
    $this->transferService = app(TransferService::class);
});

it('can transfer money between accounts with string uuids and integer amount', function () {
    $fromUuid = 'from-account-uuid';
    $toUuid = 'to-account-uuid';
    $amount = 10000;

    $this->transferService->transfer($fromUuid, $toUuid, $amount);

    WorkflowStub::assertDispatched(TransferWorkflow::class);
});

it('can transfer money with AccountUuid objects and Money object', function () {
    $fromUuid = new AccountUuid('from-account-uuid');
    $toUuid = new AccountUuid('to-account-uuid');
    $amount = new Money(10000);

    $this->transferService->transfer($fromUuid, $toUuid, $amount);

    WorkflowStub::assertDispatched(TransferWorkflow::class);
});

it('can transfer money with mixed parameter types', function () {
    $fromUuid = 'from-account-uuid';
    $toUuid = new AccountUuid('to-account-uuid');
    $amount = new Money(5000);

    $this->transferService->transfer($fromUuid, $toUuid, $amount);

    WorkflowStub::assertDispatched(TransferWorkflow::class);
});

it('dispatches workflow with correct parameters', function () {
    $fromUuid = 'from-account-uuid';
    $toUuid = 'to-account-uuid';
    $amount = 7500;

    $this->transferService->transfer($fromUuid, $toUuid, $amount);

    WorkflowStub::assertDispatched(TransferWorkflow::class, function ($workflow, $arguments) {
        return $arguments[0] instanceof AccountUuid &&
               $arguments[0]->value === 'from-account-uuid' &&
               $arguments[1] instanceof AccountUuid &&
               $arguments[1]->value === 'to-account-uuid' &&
               $arguments[2] instanceof Money &&
               $arguments[2]->amount === 7500;
    });
});

it('can handle zero amount transfer', function () {
    $fromUuid = 'from-account-uuid';
    $toUuid = 'to-account-uuid';
    $amount = 0;

    $this->transferService->transfer($fromUuid, $toUuid, $amount);

    WorkflowStub::assertDispatched(TransferWorkflow::class);
});

it('can handle large amount transfer', function () {
    $fromUuid = 'from-account-uuid';
    $toUuid = 'to-account-uuid';
    $amount = 999999999;

    $this->transferService->transfer($fromUuid, $toUuid, $amount);

    WorkflowStub::assertDispatched(TransferWorkflow::class, function ($workflow, $arguments) {
        return $arguments[2] instanceof Money &&
               $arguments[2]->amount === 999999999;
    });
});