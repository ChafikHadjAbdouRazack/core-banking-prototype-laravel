<?php

namespace Tests\Unit\Domain\Account\Services;

use App\Domain\Account\Aggregates\LedgerAggregate;
use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\Aggregates\TransferAggregate;
use App\Domain\Account\DataObjects\Account;
use App\Domain\Account\Services\AccountService;
use App\Domain\Account\Workflows\CreateAccountWorkflow;
use App\Domain\Account\Workflows\DepositAccountWorkflow;
use App\Domain\Account\Workflows\DestroyAccountWorkflow;
use App\Domain\Account\Workflows\WithdrawAccountWorkflow;
use Mockery;
use Tests\TestCase;
use Workflow\WorkflowStub;

class AccountServiceTest extends TestCase
{
    private AccountService $service;

    private LedgerAggregate $ledger;

    private TransactionAggregate $transaction;

    private TransferAggregate $transfer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledger = Mockery::mock(LedgerAggregate::class);
        $this->transaction = Mockery::mock(TransactionAggregate::class);
        $this->transfer = Mockery::mock(TransferAggregate::class);

        $this->service = new AccountService(
            $this->ledger,
            $this->transaction,
            $this->transfer
        );

        WorkflowStub::fake();
    }

    public function test_create_account_with_account_object(): void
    {
        $account = new Account([
            'name' => 'Test Account'
        ]);

        WorkflowStub::shouldReceive('make')
            ->with(CreateAccountWorkflow::class)
            ->once()
            ->andReturnSelf();
        
        WorkflowStub::shouldReceive('start')
            ->once()
            ->with(Mockery::on(function ($arg) use ($account) {
                return $arg instanceof Account && $arg->name === $account->name;
            }));

        $this->service->create($account);
    }

    public function test_create_account_with_array(): void
    {
        $accountData = [
            'name' => 'Array Account'
        ];

        WorkflowStub::shouldReceive('make')
            ->with(CreateAccountWorkflow::class)
            ->once()
            ->andReturnSelf();
        
        WorkflowStub::shouldReceive('start')
            ->once()
            ->with(Mockery::type(Account::class));

        $this->service->create($accountData);
    }

    public function test_destroy_account(): void
    {
        $uuid = 'test-account-uuid';

        WorkflowStub::shouldReceive('make')
            ->with(DestroyAccountWorkflow::class)
            ->once()
            ->andReturnSelf();
        
        WorkflowStub::shouldReceive('start')
            ->once()
            ->with(Mockery::on(function ($arg) use ($uuid) {
                return $arg->toString() === $uuid;
            }));

        $this->service->destroy($uuid);
    }

    public function test_deposit_to_account(): void
    {
        $uuid = 'deposit-account-uuid';
        $amount = 10000; // $100.00

        WorkflowStub::shouldReceive('make')
            ->with(DepositAccountWorkflow::class)
            ->once()
            ->andReturnSelf();
        
        WorkflowStub::shouldReceive('start')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg->toString() === $uuid),
                Mockery::on(fn ($arg) => $arg->getAmount() === $amount)
            );

        $this->service->deposit($uuid, $amount);
    }

    public function test_withdraw_from_account(): void
    {
        $uuid = 'withdraw-account-uuid';
        $amount = 5000; // $50.00

        WorkflowStub::shouldReceive('make')
            ->with(WithdrawAccountWorkflow::class)
            ->once()
            ->andReturnSelf();
        
        WorkflowStub::shouldReceive('start')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg->toString() === $uuid),
                Mockery::on(fn ($arg) => $arg->getAmount() === $amount)
            );

        $this->service->withdraw($uuid, $amount);
    }

    public function test_deposit_with_money_array(): void
    {
        $uuid = 'money-array-account';
        $moneyArray = [
            'currency' => 'EUR',
            'amount'   => 2500, // €25.00
        ];

        WorkflowStub::shouldReceive('make')
            ->with(DepositAccountWorkflow::class)
            ->once()
            ->andReturnSelf();
        
        WorkflowStub::shouldReceive('start')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg->toString() === $uuid),
                Mockery::on(function ($money) {
                    return $money->getCurrency() === 'EUR' && $money->getAmount() === 2500;
                })
            );

        $this->service->deposit($uuid, $moneyArray);
    }

    public function test_withdraw_with_different_amount_formats(): void
    {
        $uuid = 'multi-format-account';

        // Test with integer
        WorkflowStub::shouldReceive('make')
            ->with(WithdrawAccountWorkflow::class)
            ->once()
            ->andReturnSelf();
        
        WorkflowStub::shouldReceive('start')->once();

        $this->service->withdraw($uuid, 1000);

        // Test with money object format
        $moneyObject = [
            'currency' => 'GBP',
            'amount'   => 7500,
        ];

        WorkflowStub::shouldReceive('make')
            ->with(WithdrawAccountWorkflow::class)
            ->once()
            ->andReturnSelf();
        
        WorkflowStub::shouldReceive('start')->once();

        $this->service->withdraw($uuid, $moneyObject);
    }

    public function test_service_stores_aggregate_references(): void
    {
        // Test that aggregates are properly stored
        $reflection = new \ReflectionClass($this->service);

        $ledgerProperty = $reflection->getProperty('ledger');
        $ledgerProperty->setAccessible(true);
        $this->assertSame($this->ledger, $ledgerProperty->getValue($this->service));

        $transactionProperty = $reflection->getProperty('transaction');
        $transactionProperty->setAccessible(true);
        $this->assertSame($this->transaction, $transactionProperty->getValue($this->service));

        $transferProperty = $reflection->getProperty('transfer');
        $transferProperty->setAccessible(true);
        $this->assertSame($this->transfer, $transferProperty->getValue($this->service));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
