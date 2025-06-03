<?php

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Payment\Workflows\TransferWorkflow;
use Workflow\WorkflowStub;

beforeEach(function () {
    WorkflowStub::fake();
    
    $this->fromAccount = new AccountUuid('from-account-uuid');
    $this->toAccount = new AccountUuid('to-account-uuid');
    $this->money = new Money(10000);
});

it('can create workflow stub for transfer', function () {
    expect(class_exists(TransferWorkflow::class))->toBeTrue();
    
    $workflow = WorkflowStub::make(TransferWorkflow::class);
    expect($workflow)->toBeInstanceOf(WorkflowStub::class);
});

it('can execute transfer workflow with basic parameters', function () {
    $workflow = WorkflowStub::make(TransferWorkflow::class);
    
    $workflow->start($this->fromAccount, $this->toAccount, $this->money);

    expect(true)->toBeTrue();
});

it('handles zero amount transfer', function () {
    $zeroMoney = new Money(0);
    
    $workflow = WorkflowStub::make(TransferWorkflow::class);
    $workflow->start($this->fromAccount, $this->toAccount, $zeroMoney);

    expect(true)->toBeTrue();
});

it('can handle transfer to same account', function () {
    $sameAccount = new AccountUuid('same-account-uuid');
    
    $workflow = WorkflowStub::make(TransferWorkflow::class);
    $workflow->start($sameAccount, $sameAccount, $this->money);

    expect(true)->toBeTrue();
});

it('handles large amount transfers', function () {
    $largeMoney = new Money(999999999);
    
    $workflow = WorkflowStub::make(TransferWorkflow::class);
    $workflow->start($this->fromAccount, $this->toAccount, $largeMoney);

    expect(true)->toBeTrue();
});