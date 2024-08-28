<?php

namespace App\Domain\Account;

use App\Domain\Account\DataObjects\Account;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Events\AccountCreated;
use App\Domain\Account\Events\AccountDeleted;
use App\Domain\Account\Events\AccountLimitHit;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use App\Domain\Account\Exceptions\NotEnoughFunds;
use App\Domain\Account\Repositories\TransactionRepository;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;
use \App\Domain\Account\Repositories\SnapshotRepository;

class AccountAggregateRoot extends AggregateRoot
{
    protected int $balance = 0;

    protected int $accountLimit = 0;

    /**
     * @return TransactionRepository
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getStoredEventRepository(): TransactionRepository
    {
        return app()->make(
            abstract: TransactionRepository::class
        );
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getSnapshotRepository(): SnapshotRepository
    {
        return app()->make(
            abstract: SnapshotRepository::class
        );
    }

    /**
     * @param \App\Domain\Account\DataObjects\Account $account
     *
     * @return $this
     */
    public function createAccount(Account $account): static
    {
        $this->recordThat(
            domainEvent: new AccountCreated(
                account: $account
            )
        );

        return $this;
    }

    /**
     * @param \App\Domain\Account\DataObjects\Money $money
     *
     * @return $this
     */
    public function addMoney(Money $money): static
    {
        $this->recordThat(
            domainEvent: new MoneyAdded(
                money: $money
            )
        );

        return $this;
    }

    /**
     * @param \App\Domain\Account\Events\MoneyAdded $event
     *
     * @return void
     */
    public function applyMoneyAdded(MoneyAdded $event): void
    {
        $this->balance += $event->money->amount();
    }

    /**
     * @param \App\Domain\Account\DataObjects\Money $money
     *
     * @return void
     */
    public function subtractMoney(Money $money): void
    {
        if (!$this->hasSufficientFundsToSubtractAmount($money)) {
            $this->recordThat(new AccountLimitHit());

            $this->persist();

            throw new NotEnoughFunds;
        }

        $this->recordThat(new MoneySubtracted($money));
    }

    /**
     * @param \App\Domain\Account\Events\MoneySubtracted $event
     *
     * @return void
     */
    public function applyMoneySubtracted(MoneySubtracted $event): void
    {
        $this->balance -= $event->money->amount();
    }

    /**
     * @return $this
     */
    public function deleteAccount(): static
    {
        $this->recordThat(new AccountDeleted());

        return $this;
    }

    /**
     * @param \App\Domain\Account\DataObjects\Money $money
     *
     * @return bool
     */
    private function hasSufficientFundsToSubtractAmount(Money $money): bool
    {
        return $this->balance - $money->amount() >= $this->accountLimit;
    }
}
