<?php

namespace Tests\Domain\Account\Aggregates;

use App\Domain\Account\Aggregates\LedgerAggregate;
use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\DataObjects\Account;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Events\AccountCreated;
use App\Domain\Account\Events\AccountDeleted;
use App\Domain\Account\Events\AccountLimitHit;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use App\Domain\Account\Exceptions\NotEnoughFunds;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class TransactionAggregateTest extends TestCase
{
    private const string ACCOUNT_UUID = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

    private const string ACCOUNT_NAME = 'fake-account';

    /** @test */
    public function can_add_money(): void
    {
        TransactionAggregate::fake(self::ACCOUNT_UUID)
            ->given([
                new AccountCreated($this->fakeAccount())
            ])
            ->when(function (TransactionAggregate $transactions): void {
                $transactions->credit(
                    $this->money(10)
                );
            })
            ->assertRecorded([
                new MoneyAdded(
                    $this->money(10)
                )
            ]);
    }

    /** @test */
    public function can_subtract_money(): void
    {
        TransactionAggregate::fake(self::ACCOUNT_UUID)
            ->given([
                new AccountCreated($this->fakeAccount()),
                new MoneyAdded($this->money(10))
            ])
            ->when(function (TransactionAggregate $transactions): void {
                $transactions->debit(
                    $this->money(10)
                );
            })
            ->assertRecorded([
                new MoneySubtracted(
                    $this->money(10)
                ),
            ])
            ->assertNotRecorded(AccountLimitHit::class);
    }

    /** @test */
    public function cannot_subtract_money_when_money_below_account_limit(): void
    {
        TransactionAggregate::fake(self::ACCOUNT_UUID)
            ->given([
                new AccountCreated($this->fakeAccount()),
            ])
            ->when(function (TransactionAggregate $transactions): void {
                $this->assertExceptionThrown(function () use ($transactions) {
                    $transactions->debit(
                        $this->money(1)
                    );

                }, NotEnoughFunds::class);
            })
            ->assertApplied([
                new AccountCreated($this->fakeAccount()),
                new AccountLimitHit()
            ])
            ->assertNotRecorded(MoneySubtracted::class);

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

    /**
     * @param int $amount
     *
     * @return \App\Domain\Account\DataObjects\Money
     */
    function money( int $amount ): Money
    {
        return hydrate( Money::class, [ 'amount' => $amount ] );
    }
}
