<?php

namespace Tests\Unit\Domain\Account\Actions;

use App\Domain\Account\Actions\DebitAccount;
use App\Domain\Account\Events\AssetBalanceSubtracted;
use App\Domain\Account\Repositories\AccountRepository;
use App\Models\Account;
use App\Models\AccountBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class DebitAccountTest extends TestCase
{
    use RefreshDatabase;

    private DebitAccount $action;
    private AccountRepository $accountRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->accountRepository = Mockery::mock(AccountRepository::class);
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
            'asset_code' => 'USD',
            'balance' => 5000, // $50.00
        ]);

        // Mock repository
        $this->accountRepository->shouldReceive('findByUuid')
            ->with('account-123')
            ->andReturn($account);

        // Create event
        $event = Mockery::mock(AssetBalanceSubtracted::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn('account-123');
        $event->shouldReceive('assetCode', 'getAssetCode')->andReturn('USD');
        $event->shouldReceive('amount', 'getAmount')->andReturn(2000); // $20.00

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

        // Mock repository
        $this->accountRepository->shouldReceive('findByUuid')
            ->with('account-456')
            ->andReturn($account);

        // Create event
        $event = Mockery::mock(AssetBalanceSubtracted::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn('account-456');
        $event->shouldReceive('assetCode', 'getAssetCode')->andReturn('EUR');
        $event->shouldReceive('amount', 'getAmount')->andReturn(1000);

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
            'asset_code' => 'USD',
            'balance' => 1000, // $10.00
        ]);

        // Mock repository
        $this->accountRepository->shouldReceive('findByUuid')
            ->with('account-789')
            ->andReturn($account);

        // Create event that would overdraw
        $event = Mockery::mock(AssetBalanceSubtracted::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn('account-789');
        $event->shouldReceive('assetCode', 'getAssetCode')->andReturn('USD');
        $event->shouldReceive('amount', 'getAmount')->andReturn(2000); // $20.00

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
            'asset_code' => 'BTC',
            'balance' => 100000000, // 1 BTC
        ]);

        // Mock repository
        $this->accountRepository->shouldReceive('findByUuid')
            ->with('exact-balance-account')
            ->andReturn($account);

        // Create event for exact balance
        $event = Mockery::mock(AssetBalanceSubtracted::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn('exact-balance-account');
        $event->shouldReceive('assetCode', 'getAssetCode')->andReturn('BTC');
        $event->shouldReceive('amount', 'getAmount')->andReturn(100000000); // 1 BTC

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
            'asset_code' => 'EUR',
            'balance' => 10000, // €100.00
        ]);

        // Mock repository
        $this->accountRepository->shouldReceive('findByUuid')
            ->with('multi-debit-account')
            ->andReturn($account);

        // First debit
        $event1 = Mockery::mock(AssetBalanceSubtracted::class);
        $event1->shouldReceive('aggregateRootUuid')->andReturn('multi-debit-account');
        $event1->shouldReceive('assetCode', 'getAssetCode')->andReturn('EUR');
        $event1->shouldReceive('amount', 'getAmount')->andReturn(3000); // €30.00

        $this->action->__invoke($event1);

        // Second debit
        $event2 = Mockery::mock(AssetBalanceSubtracted::class);
        $event2->shouldReceive('aggregateRootUuid')->andReturn('multi-debit-account');
        $event2->shouldReceive('assetCode', 'getAssetCode')->andReturn('EUR');
        $event2->shouldReceive('amount', 'getAmount')->andReturn(2000); // €20.00

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