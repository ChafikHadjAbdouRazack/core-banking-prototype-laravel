<?php

namespace Tests\Unit\Domain\Account\Actions;

use App\Domain\Account\Actions\CreditAccount;
use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\Events\AssetBalanceAdded;
use App\Domain\Account\Repositories\AccountRepository;
use App\Models\Account;
use App\Models\AccountBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CreditAccountTest extends TestCase
{
    use RefreshDatabase;

    private CreditAccount $action;

    private AccountRepository $accountRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountRepository = new AccountRepository(new Account());
        $this->action = new CreditAccount($this->accountRepository);
    }

    public function test_credits_existing_balance(): void
    {
        // Create account
        $account = Account::factory()->create([
            'uuid' => 'account-123',
            'name' => 'Test Account',
        ]);

        // Create existing balance
        $balance = AccountBalance::create([
            'account_uuid' => 'account-123',
            'asset_code'   => 'USD',
            'balance'      => 1000, // $10.00
        ]);

        // Repository will find the account by UUID

        // Create event mock with constructor bypass
        $event = Mockery::mock(AssetBalanceAdded::class)->makePartial();
        $event->shouldReceive('aggregateRootUuid')->andReturn('account-123');
        
        // Use reflection to set readonly properties
        $reflection = new \ReflectionClass($event);
        $assetCodeProp = $reflection->getProperty('assetCode');
        $assetCodeProp->setAccessible(true);
        $assetCodeProp->setValue($event, 'USD');
        
        $amountProp = $reflection->getProperty('amount');
        $amountProp->setAccessible(true);
        $amountProp->setValue($event, 500); // $5.00

        // Execute
        $result = $this->action->__invoke($event);

        // Assert
        $this->assertInstanceOf(Account::class, $result);

        $updatedBalance = AccountBalance::where('account_uuid', 'account-123')
            ->where('asset_code', 'USD')
            ->first();

        $this->assertEquals(1500, $updatedBalance->balance); // $15.00
    }

    public function test_creates_new_balance_if_not_exists(): void
    {
        // Create account
        $account = Account::factory()->create([
            'uuid' => 'account-456',
            'name' => 'New Account',
        ]);

        // Repository will find the account by UUID

        // Create event with property access
        $event = Mockery::mock(AssetBalanceAdded::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn('account-456');
        $event->assetCode = 'EUR';
        $event->amount = 2500; // €25.00

        // Execute
        $result = $this->action->__invoke($event);

        // Assert
        $this->assertInstanceOf(Account::class, $result);

        $balance = AccountBalance::where('account_uuid', 'account-456')
            ->where('asset_code', 'EUR')
            ->first();

        $this->assertNotNull($balance);
        $this->assertEquals(2500, $balance->balance); // €25.00
    }

    public function test_handles_multiple_credits_to_same_account(): void
    {
        // Create account
        $account = Account::factory()->create([
            'uuid' => 'account-789',
            'name' => 'Multi Credit Account',
        ]);

        // Repository will find the account by UUID

        // First credit
        $event1 = Mockery::mock(AssetBalanceAdded::class);
        $event1->shouldReceive('aggregateRootUuid')->andReturn('account-789');
        $event1->assetCode = 'BTC';
        $event1->amount = 100000; // 0.001 BTC

        $this->action->__invoke($event1);

        // Second credit
        $event2 = Mockery::mock(AssetBalanceAdded::class);
        $event2->shouldReceive('aggregateRootUuid')->andReturn('account-789');
        $event2->assetCode = 'BTC';
        $event2->amount = 50000; // 0.0005 BTC

        $this->action->__invoke($event2);

        // Assert
        $balance = AccountBalance::where('account_uuid', 'account-789')
            ->where('asset_code', 'BTC')
            ->first();

        $this->assertEquals(150000, $balance->balance); // 0.0015 BTC
    }

    public function test_handles_different_asset_codes(): void
    {
        // Create account
        $account = Account::factory()->create([
            'uuid' => 'multi-asset-account',
            'name' => 'Multi Asset Account',
        ]);

        // Repository will find the account by UUID

        // Credit USD
        $usdEvent = Mockery::mock(AssetBalanceAdded::class);
        $usdEvent->shouldReceive('aggregateRootUuid')->andReturn('multi-asset-account');
        $usdEvent->assetCode = 'USD';
        $usdEvent->amount = 10000; // $100.00

        $this->action->__invoke($usdEvent);

        // Credit EUR
        $eurEvent = Mockery::mock(AssetBalanceAdded::class);
        $eurEvent->shouldReceive('aggregateRootUuid')->andReturn('multi-asset-account');
        $eurEvent->assetCode = 'EUR';
        $eurEvent->amount = 5000; // €50.00

        $this->action->__invoke($eurEvent);

        // Assert
        $usdBalance = AccountBalance::where('account_uuid', 'multi-asset-account')
            ->where('asset_code', 'USD')
            ->first();
        $eurBalance = AccountBalance::where('account_uuid', 'multi-asset-account')
            ->where('asset_code', 'EUR')
            ->first();

        $this->assertEquals(10000, $usdBalance->balance);
        $this->assertEquals(5000, $eurBalance->balance);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
