<?php

namespace Tests\Domain\Account\Workflows;

use App\Domain\Account\Aggregates\LedgerAggregate;
use App\Domain\Account\DataObjects\Account;
use App\Domain\Account\Workflows\CreateAccountActivity;
use App\Domain\Account\Workflows\CreateAccountWorkflow;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Workflow\WorkflowStub;

class CreateAccountWorkflowTest extends TestCase
{
    private const string ACCOUNT_UUID = 'account-uuid';

    private const string ACCOUNT_NAME = 'fake-account';

    #[Test]
    public function it_calls_account_creation_activity(): void
    {
        WorkflowStub::fake();
        WorkflowStub::mock(CreateAccountActivity::class, self::ACCOUNT_UUID);

        $account = $this->fakeAccount();

        $workflow = WorkflowStub::make(CreateAccountWorkflow::class);
        $workflow->start( $account );

        WorkflowStub::assertDispatched(CreateAccountActivity::class);
        $this->assertSame($workflow->output(), self::ACCOUNT_UUID);
    }

    /**
     * @return \App\Domain\Account\DataObjects\Account
     */
    protected function fakeAccount(): Account
    {
        return hydrate(
            Account::class,
            [
                'name'      => self::ACCOUNT_NAME,
                'user_uuid' => $this->business_user->uuid,
            ]
        );
    }
}
