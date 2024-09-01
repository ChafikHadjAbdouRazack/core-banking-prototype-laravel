<?php

namespace Tests\Domain\Account\Projectors;

use App\Domain\Account\Aggregates\LedgerAggregate;
use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Repositories\TransactionRepository;
use App\Models\Account;
use App\Models\Ledger;
use App\Models\Transaction;
use Tests\TestCase;

class AccountProjectorTest extends TestCase
{
    /** @test */
    public function test_create(): void
    {
        $this->assertDatabaseHas((new Account())->getTable(), [
            'user_uuid' => $this->business_user->uuid,
            'uuid' => $this->account->uuid,
        ]);

        $this->assertTrue($this->account->user->is($this->business_user));
    }

    /** @test */
    public function test_add_money(): void
    {
        $this->assertEquals(0, $this->account->balance);

        TransactionAggregate::retrieve($this->account->uuid)
            ->credit(
                hydrate( Money::class, ['amount' => 10] )
            )
            ->persist();

        $this->account->refresh();

        $this->assertEquals(10, $this->account->balance);
    }

    /** @test */
    public function test_subtract_money(): void
    {
        $this->assertEquals(0, $this->account->balance);

        $this->account->addMoney(20);

        TransactionAggregate::retrieve($this->account->uuid)
            ->applyMoneyAdded( new MoneyAdded( hydrate( Money::class, ['amount' => 20] ) ) )
            ->debit(
                hydrate( Money::class, ['amount' => 10] )
            )->persist();

        $this->account->refresh();

        $this->assertEquals(10, $this->account->balance);
    }

    /** @test */
    public function test_delete_account(): void
    {
        LedgerAggregate::retrieve($this->account->uuid)
            ->deleteAccount()
            ->persist();

        $this->assertDatabaseMissing((new Account())->getTable(), [
            'user_uuid' => $this->business_user->uuid,
            'uuid' => $this->account->uuid,
        ]);
    }
}
