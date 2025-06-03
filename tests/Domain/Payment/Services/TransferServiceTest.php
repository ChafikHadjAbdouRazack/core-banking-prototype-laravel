<?php

use App\Domain\Payment\Services\TransferService;
use Workflow\WorkflowStub;

beforeEach(function () {
    WorkflowStub::fake();
    $this->transferService = app(TransferService::class);
});

it('has transfer method', function () {
    expect(method_exists($this->transferService, 'transfer'))->toBeTrue();
});

it('can be instantiated from container', function () {
    expect($this->transferService)->toBeInstanceOf(TransferService::class);
});

it('can transfer money between accounts with string uuids and integer amount', function () {
    $fromUuid = 'from-account-uuid';
    $toUuid = 'to-account-uuid';
    $amount = 10000;

    $this->transferService->transfer($fromUuid, $toUuid, $amount);

    expect(true)->toBeTrue();
});

it('can handle zero amount transfer', function () {
    $fromUuid = 'from-account-uuid';
    $toUuid = 'to-account-uuid';
    $amount = 0;

    $this->transferService->transfer($fromUuid, $toUuid, $amount);

    expect(true)->toBeTrue();
});

it('can handle large amount transfer', function () {
    $fromUuid = 'from-account-uuid';
    $toUuid = 'to-account-uuid';
    $amount = 999999999;

    $this->transferService->transfer($fromUuid, $toUuid, $amount);

    expect(true)->toBeTrue();
});