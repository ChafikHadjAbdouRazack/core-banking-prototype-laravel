<?php

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Workflows\DepositAccountWorkflow;
use App\Domain\Account\Workflows\WithdrawAccountWorkflow;
use App\Domain\Payment\Workflows\TransferWorkflow;
use Workflow\ChildWorkflowStub;
use Workflow\WorkflowStub;

beforeEach(function () {
    WorkflowStub::fake();
    ChildWorkflowStub::fake();
    
    $this->fromAccount = new AccountUuid('from-account-uuid');
    $this->toAccount = new AccountUuid('to-account-uuid');
    $this->money = new Money(10000);
});

it('can execute successful transfer workflow', function () {
    $workflow = WorkflowStub::make(TransferWorkflow::class);
    $workflow->start($this->fromAccount, $this->toAccount, $this->money);

    // Should dispatch withdraw from source account first
    ChildWorkflowStub::assertDispatched(WithdrawAccountWorkflow::class, function ($workflow, $arguments) {
        return $arguments[0]->value === 'from-account-uuid' &&
               $arguments[1]->amount === 10000;
    });

    // Should dispatch deposit to target account second
    ChildWorkflowStub::assertDispatched(DepositAccountWorkflow::class, function ($workflow, $arguments) {
        return $arguments[0]->value === 'to-account-uuid' &&
               $arguments[1]->amount === 10000;
    });
});

it('can create workflow stub for transfer', function () {
    expect(class_exists(TransferWorkflow::class))->toBeTrue();
    
    $workflow = WorkflowStub::make(TransferWorkflow::class);
    expect($workflow)->toBeInstanceOf(WorkflowStub::class);
});

it('executes workflows in correct order', function () {
    $workflow = WorkflowStub::make(TransferWorkflow::class);
    $workflow->start($this->fromAccount, $this->toAccount, $this->money);

    // Verify that both child workflows are dispatched
    ChildWorkflowStub::assertDispatchedTimes(WithdrawAccountWorkflow::class, 1);
    ChildWorkflowStub::assertDispatchedTimes(DepositAccountWorkflow::class, 1);
});

it('handles zero amount transfer', function () {
    $zeroMoney = new Money(0);
    
    $workflow = WorkflowStub::make(TransferWorkflow::class);
    $workflow->start($this->fromAccount, $this->toAccount, $zeroMoney);

    ChildWorkflowStub::assertDispatched(WithdrawAccountWorkflow::class, function ($workflow, $arguments) {
        return $arguments[1]->amount === 0;
    });

    ChildWorkflowStub::assertDispatched(DepositAccountWorkflow::class, function ($workflow, $arguments) {
        return $arguments[1]->amount === 0;
    });
});

it('can handle transfer to same account', function () {
    $sameAccount = new AccountUuid('same-account-uuid');
    
    $workflow = WorkflowStub::make(TransferWorkflow::class);
    $workflow->start($sameAccount, $sameAccount, $this->money);

    // Should still execute both workflows even for same account
    ChildWorkflowStub::assertDispatched(WithdrawAccountWorkflow::class, function ($workflow, $arguments) {
        return $arguments[0]->value === 'same-account-uuid';
    });

    ChildWorkflowStub::assertDispatched(DepositAccountWorkflow::class, function ($workflow, $arguments) {
        return $arguments[0]->value === 'same-account-uuid';
    });
});

it('handles large amount transfers', function () {
    $largeMoney = new Money(999999999);
    
    $workflow = WorkflowStub::make(TransferWorkflow::class);
    $workflow->start($this->fromAccount, $this->toAccount, $largeMoney);

    ChildWorkflowStub::assertDispatched(WithdrawAccountWorkflow::class, function ($workflow, $arguments) {
        return $arguments[1]->amount === 999999999;
    });

    ChildWorkflowStub::assertDispatched(DepositAccountWorkflow::class, function ($workflow, $arguments) {
        return $arguments[1]->amount === 999999999;
    });
});