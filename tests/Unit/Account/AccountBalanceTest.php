<?php

namespace Tests\Unit\Account;

use Tests\TestCase;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Domain\Asset\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class AccountBalanceTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations to create assets
        $this->artisan('migrate');
    }
    
    #[Test]
    public function it_can_credit_balance()
    {
        $balance = AccountBalance::factory()->zero()->create();
        
        $balance->credit(5000);
        
        expect($balance->balance)->toBe(5000);
        $this->assertDatabaseHas('account_balances', [
            'id' => $balance->id,
            'balance' => 5000,
        ]);
    }
    
    #[Test]
    public function it_can_debit_balance()
    {
        $balance = AccountBalance::factory()->withBalance(10000)->create();
        
        $balance->debit(3000);
        
        expect($balance->balance)->toBe(7000);
        $this->assertDatabaseHas('account_balances', [
            'id' => $balance->id,
            'balance' => 7000,
        ]);
    }
    
    #[Test]
    public function it_throws_exception_when_debiting_more_than_balance()
    {
        $balance = AccountBalance::factory()->withBalance(1000)->create();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');
        
        $balance->debit(2000);
    }
    
    #[Test]
    public function it_can_check_sufficient_balance()
    {
        $balance = AccountBalance::factory()->withBalance(5000)->create();
        
        expect($balance->hasSufficientBalance(3000))->toBeTrue();
        expect($balance->hasSufficientBalance(5000))->toBeTrue();
        expect($balance->hasSufficientBalance(5001))->toBeFalse();
    }
    
    #[Test]
    public function it_formats_balance_with_asset_symbol()
    {
        $usd = Asset::where('code', 'USD')->first();
        $balance = AccountBalance::factory()
            ->forAsset($usd)
            ->withBalance(12345)
            ->create();
        
        expect($balance->getFormattedBalance())->toBe('$123.45');
    }
    
    #[Test]
    public function it_has_account_relationship()
    {
        $account = Account::factory()->create();
        $balance = AccountBalance::factory()->forAccount($account)->create();
        
        expect($balance->account)->toBeInstanceOf(Account::class);
        expect($balance->account->uuid)->toBe($account->uuid);
    }
    
    #[Test]
    public function it_has_asset_relationship()
    {
        $asset = Asset::where('code', 'EUR')->first();
        $balance = AccountBalance::factory()->forAsset($asset)->create();
        
        expect($balance->asset)->toBeInstanceOf(Asset::class);
        expect($balance->asset->code)->toBe('EUR');
    }
    
    #[Test]
    public function it_can_scope_positive_balances()
    {
        AccountBalance::factory()->count(3)->withBalance(1000)->create();
        AccountBalance::factory()->count(2)->zero()->create();
        
        $positiveBalances = AccountBalance::positive()->get();
        
        expect($positiveBalances)->toHaveCount(3);
        expect(AccountBalance::count())->toBe(5);
    }
    
    #[Test]
    public function it_can_scope_by_asset()
    {
        AccountBalance::factory()->count(3)->usd()->create();
        AccountBalance::factory()->count(2)->eur()->create();
        AccountBalance::factory()->count(1)->btc()->create();
        
        expect(AccountBalance::forAsset('USD')->count())->toBe(3);
        expect(AccountBalance::forAsset('EUR')->count())->toBe(2);
        expect(AccountBalance::forAsset('BTC')->count())->toBe(1);
    }
    
    #[Test]
    public function it_enforces_unique_constraint_on_account_and_asset()
    {
        $account = Account::factory()->create();
        
        AccountBalance::factory()
            ->forAccount($account)
            ->forAsset('USD')
            ->create();
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        AccountBalance::factory()
            ->forAccount($account)
            ->forAsset('USD')
            ->create();
    }
}