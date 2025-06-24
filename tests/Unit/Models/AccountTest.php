<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Account;
use App\Models\AccountBalance;
use Tests\TestCase;

class AccountTest extends TestCase
{
    public function test_account_factory_creates_account()
    {
        $user = User::factory()->create();
        $account = Account::factory()->forUser($user)->create();
        
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'user_uuid' => $user->uuid,
        ]);
    }

    public function test_account_has_uuid()
    {
        $account = Account::factory()->create();
        
        $this->assertNotNull($account->uuid);
        $this->assertIsString($account->uuid);
    }

    public function test_account_belongs_to_user()
    {
        $user = User::factory()->create();
        $account = Account::factory()->forUser($user)->create();
        
        $this->assertEquals($user->uuid, $account->user_uuid);
        $this->assertInstanceOf(User::class, $account->user);
    }

    public function test_account_has_balances_relationship()
    {
        $account = Account::factory()->create();
        $balance = AccountBalance::factory()->create(['account_uuid' => $account->uuid]);
        
        $this->assertTrue($account->balances()->exists());
        $this->assertTrue($account->balances->contains($balance));
    }

    public function test_account_fillable_attributes()
    {
        $account = new Account();
        $fillable = $account->getFillable();
        
        $this->assertContains('user_uuid', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('balance', $fillable);
    }

    public function test_account_default_balance_is_zero()
    {
        $account = Account::factory()->create();
        
        $this->assertEquals(0, $account->balance);
    }

    public function test_account_can_be_frozen()
    {
        $account = Account::factory()->create(['frozen' => true]);
        
        $this->assertTrue($account->frozen);
    }

    public function test_account_default_not_frozen()
    {
        $account = Account::factory()->create();
        
        $this->assertFalse($account->frozen);
    }
}