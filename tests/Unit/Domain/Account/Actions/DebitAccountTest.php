<?php

namespace Tests\Unit\Domain\Account\Actions;

use App\Domain\Account\Actions\DebitAccount;
use App\Domain\Account\Events\AssetBalanceSubtracted;
use App\Domain\Account\Repositories\AccountRepository;
use App\Models\Account;
use App\Models\AccountBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DebitAccountTest extends TestCase
{
    use RefreshDatabase;

    private DebitAccount $action;

    private AccountRepository $accountRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountRepository = new AccountRepository(new Account());
        $this->action = new DebitAccount($this->accountRepository);
    }

    public function test_debits_existing_balance(): void
    {
        // Create account
        $account = Account::factory()->create([
            'uuid' => 'account-123',
            'name' => 'Test Account',
        ]);

        // Create existing balance
        AccountBalance::create([
            'account_uuid' => 'account-123',
            'asset_code'   => 'USD',
            'balance'      => 5000, // $50.00
        ]);

        // Repository will find the account by UUID

        // Create event
        $event = Mockery::mock(AssetBalanceSubtracted::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn('account-123');
        $event->assetCode = 'USD';
        $event->amount = 2000; // $20.00

        // Execute
        $result = $this->action->__invoke($event);

        // Assert
        $this->assertInstanceOf(Account::class, $result);

        $updatedBalance = AccountBalance::where('account_uuid', 'account-123')
            ->where('asset_code', 'USD')
            ->first();

        $this->assertEquals(3000, $updatedBalance->balance); // $30.00
    }

    public function test_throws_exception_if_balance_not_found(): void
    {
        // Create account
        $account = Account::factory()->create([
            'uuid' => 'account-456',
            'name' => 'No Balance Account',
        ]);

        // Repository will find the account by UUID

        // Create event
        $event = Mockery::mock(AssetBalanceSubtracted::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn('account-456');
        $event->assetCode = 'EUR';
        $event->amount = 1000;

        // Assert exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Asset balance not found for EUR');

        // Execute
        $this->action->__invoke($event);
    }

    public function test_throws_exception_if_insufficient_balance(): void
    {
        // Create account
        $account = Account::factory()->create([
            'uuid' => 'account-789',
            'name' => 'Low Balance Account',
        ]);

        // Create existing balance
        AccountBalance::create([
            'account_uuid' => 'account-789',
            'asset_code'   => 'USD',
            'balance'      => 1000, // $10.00
        ]);

        // Repository will find the account by UUID

        // Create event that would overdraw
        $event = Mockery::mock(AssetBalanceSubtracted::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn('account-789');
        $event->assetCode = 'USD';
        $event->amount = 2000; // $20.00

        // Assert exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance for USD');

        // Execute
        $this->action->__invoke($event);
    }

    public function test_handles_exact_balance_debit(): void
    {
        // Create account
        $account = Account::factory()->create([
            'uuid' => 'exact-balance-account',
            'name' => 'Exact Balance Account',
        ]);

        // Create existing balance
        AccountBalance::create([
            'account_uuid' => 'exact-balance-account',
            'asset_code'   => 'BTC',
            'balance'      => 100000000, // 1 BTC
        ]);

        // Repository will find the account by UUID

        // Create event for exact balance
        $event = Mockery::mock(AssetBalanceSubtracted::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn('exact-balance-account');
        $event->assetCode = 'BTC';
        $event->amount = 100000000; // 1 BTC

        // Execute
        $result = $this->action->__invoke($event);

        // Assert
        $updatedBalance = AccountBalance::where('account_uuid', 'exact-balance-account')
            ->where('asset_code', 'BTC')
            ->first();

        $this->assertEquals(0, $updatedBalance->balance);
    }

    public function test_handles_multiple_debits(): void
    {
        // Create account
        $account = Account::factory()->create([
            'uuid' => 'multi-debit-account',
            'name' => 'Multi Debit Account',
        ]);

        // Create existing balance
        AccountBalance::create([
            'account_uuid' => 'multi-debit-account',
            'asset_code'   => 'EUR',
            'balance'      => 10000, // €100.00
        ]);

        // Repository will find the account by UUID

        // First debit
        $event1 = Mockery::mock(AssetBalanceSubtracted::class);
        $event1->shouldReceive('aggregateRootUuid')->andReturn('multi-debit-account');
        $event1->assetCode = 'EUR';
        $event1->amount = 3000; // €30.00

        $this->action->__invoke($event1);

        // Second debit
        $event2 = Mockery::mock(AssetBalanceSubtracted::class);
        $event2->shouldReceive('aggregateRootUuid')->andReturn('multi-debit-account');
        $event2->assetCode = 'EUR';
        $event2->amount = 2000; // €20.00

        $this->action->__invoke($event2);

        // Assert
        $balance = AccountBalance::where('account_uuid', 'multi-debit-account')
            ->where('asset_code', 'EUR')
            ->first();

        $this->assertEquals(5000, $balance->balance); // €50.00 remaining
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
