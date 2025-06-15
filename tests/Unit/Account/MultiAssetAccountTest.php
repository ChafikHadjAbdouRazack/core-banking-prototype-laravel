<?php

namespace Tests\Unit\Account;

use Tests\TestCase;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Domain\Asset\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class MultiAssetAccountTest extends TestCase
{
    use RefreshDatabase;
    
    private Account $account;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations to create assets
        $this->artisan('migrate');
        
        $this->account = Account::factory()->create();
    }
    
    #[Test]
    public function it_can_add_balance_for_different_assets()
    {
        $this->account->addBalance('USD', 10000); // $100.00
        $this->account->addBalance('EUR', 5000);  // €50.00
        $this->account->addBalance('BTC', 100000000); // 1 BTC
        
        expect($this->account->getBalance('USD'))->toBe(10000);
        expect($this->account->getBalance('EUR'))->toBe(5000);
        expect($this->account->getBalance('BTC'))->toBe(100000000);
        expect($this->account->balances)->toHaveCount(3);
    }
    
    #[Test]
    public function it_returns_zero_balance_for_non_existing_asset()
    {
        expect($this->account->getBalance('GBP'))->toBe(0);
    }
    
    #[Test]
    public function it_can_subtract_balance_from_asset()
    {
        $this->account->addBalance('USD', 10000); // $100.00
        
        $balance = $this->account->subtractBalance('USD', 2500); // $25.00
        
        expect($balance->balance)->toBe(7500); // $75.00
        expect($this->account->getBalance('USD'))->toBe(7500);
    }
    
    #[Test]
    public function it_throws_exception_when_subtracting_more_than_balance()
    {
        $this->account->addBalance('USD', 1000); // $10.00
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient USD balance');
        
        $this->account->subtractBalance('USD', 2000); // $20.00
    }
    
    #[Test]
    public function it_can_check_sufficient_balance()
    {
        $this->account->addBalance('USD', 5000); // $50.00
        
        expect($this->account->hasSufficientBalance('USD', 3000))->toBeTrue();
        expect($this->account->hasSufficientBalance('USD', 5000))->toBeTrue();
        expect($this->account->hasSufficientBalance('USD', 6000))->toBeFalse();
        expect($this->account->hasSufficientBalance('EUR', 100))->toBeFalse();
    }
    
    #[Test]
    public function it_can_get_active_balances()
    {
        $this->account->addBalance('USD', 10000);
        $this->account->addBalance('EUR', 5000);
        $this->account->addBalance('GBP', 0); // Zero balance
        
        $activeBalances = $this->account->getActiveBalances();
        
        expect($activeBalances)->toHaveCount(2);
        expect($activeBalances->pluck('asset_code')->toArray())->toContain('USD', 'EUR');
        expect($activeBalances->pluck('asset_code')->toArray())->not->toContain('GBP');
    }
    
    #[Test]
    public function it_maintains_backward_compatibility_with_balance_attribute()
    {
        $this->account->addBalance('USD', 12345);
        
        // The balance attribute should return USD balance
        expect($this->account->balance)->toBe(12345);
        expect($this->account->toArray()['balance'])->toBe(12345);
    }
    
    #[Test]
    public function it_maintains_backward_compatibility_with_money_methods()
    {
        $initialBalance = $this->account->getBalance('USD');
        
        $this->account->addMoney(5000);
        expect($this->account->getBalance('USD'))->toBe($initialBalance + 5000);
        
        $this->account->subtractMoney(2000);
        expect($this->account->getBalance('USD'))->toBe($initialBalance + 3000);
    }
    
    #[Test]
    public function it_creates_balance_entry_on_first_add()
    {
        expect($this->account->balances()->count())->toBe(0);
        
        $this->account->addBalance('EUR', 1000);
        
        expect($this->account->balances()->count())->toBe(1);
        
        $balance = $this->account->getBalanceForAsset('EUR');
        expect($balance)->toBeInstanceOf(AccountBalance::class);
        expect($balance->asset_code)->toBe('EUR');
        expect($balance->balance)->toBe(1000);
    }
    
    #[Test]
    public function it_increments_existing_balance()
    {
        $this->account->addBalance('USD', 1000);
        $this->account->addBalance('USD', 2000);
        
        expect($this->account->getBalance('USD'))->toBe(3000);
        expect($this->account->balances()->count())->toBe(1);
    }
}