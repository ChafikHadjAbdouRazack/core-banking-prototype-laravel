<?php

namespace Tests\Domain\Account\Workflows;

use App\Domain\Account\Aggregates\LedgerAggregate;
use App\Domain\Account\DataObjects\Account;
use App\Domain\Account\Workflows\CreateAccountActivity;
use App\Domain\Account\Workflows\CreateAccountWorkflow;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\DomainTestCase;
use Workflow\Models\StoredWorkflow;
use Workflow\WorkflowStub;

class CreateAccountActivityTest extends DomainTestCase
{
    private const string ACCOUNT_UUID = 'account-uuid';

    private const string ACCOUNT_NAME = 'fake-account';

    #[Test]
    public function it_creates_account_using_ledger(): void
    {
        $ledgerMock = Mockery::mock(LedgerAggregate::class);
        $ledgerMock->expects('retrieve')
            ->andReturnSelf();

        $ledgerMock->expects('createAccount')
            ->with(Mockery::type(Account::class))
            ->andReturnSelf();

        $ledgerMock->expects('persist')
            ->andReturnSelf();

        $workflow = WorkflowStub::make(CreateAccountWorkflow::class);
        $storedWorkflow = StoredWorkflow::findOrFail($workflow->id());

        $activity = new CreateAccountActivity(
            0,
            now()->toDateTimeString(),
            $storedWorkflow,
            $this->fakeAccount(),
            $ledgerMock
        );

        $activity->handle();
    }

    protected function fakeAccount(): Account
    {
        return hydrate(
            Account::class,
            [
                'name' => self::ACCOUNT_NAME,
                'user_uuid' => $this->business_user->uuid,
            ]
        );
    }
}
