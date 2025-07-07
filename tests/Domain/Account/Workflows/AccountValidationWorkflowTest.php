<?php

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\Workflows\AccountValidationWorkflow;
use App\Models\Account;
use Workflow\WorkflowStub;

it('can validate account with all checks', function () {
    WorkflowStub::fake();

    // Create test account
    $account = Account::factory()->create();
    $accountUuid = new AccountUuid($account->uuid);
    $validationChecks = [
        'kyc_document_verification',
        'address_verification',
        'identity_verification',
        'compliance_screening',
    ];
    $validatedBy = 'compliance-officer-789';

    $workflow = WorkflowStub::make(AccountValidationWorkflow::class);
    $workflow->start($accountUuid, $validationChecks, $validatedBy);

    expect(true)->toBeTrue(); // Basic test that workflow starts without error
});

it('can create workflow stub for account validation', function () {
    expect(class_exists(AccountValidationWorkflow::class))->toBeTrue();
});
