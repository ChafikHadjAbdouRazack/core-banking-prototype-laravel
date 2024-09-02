<?php

namespace App\Domain\Account\Aggregates;

use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Events\AccountLimitHit;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use App\Domain\Account\Exceptions\InvalidHashException;
use App\Domain\Account\Exceptions\NotEnoughFunds;
use App\Domain\Account\Repositories\TransactionRepository;
use App\Domain\Account\Repositories\TransactionSnapshotRepository;
use App\Domain\Account\Utils\ValidatesHash;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class TransactionAggregate extends AggregateRoot
{
    use ValidatesHash;

    /**
     * @param int $balance
     * @param int $accountLimit
     */
    public function __construct(
        protected int $balance = 0,
        protected int $accountLimit = 0,
    ) {
    }

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
    protected function getSnapshotRepository(): TransactionSnapshotRepository
    {
        return app()->make(
            abstract: TransactionSnapshotRepository::class
        );
    }

    /**
     * @param \App\Domain\Account\DataObjects\Money $money
     *
     * @return $this
     */
    public function credit( Money $money ): static
    {
        $this->recordThat(
            domainEvent: new MoneyAdded(
                money: $money,
                hash: $this->generateHash( $money ),
            )
        );

        return $this;
    }

    /**
     * @param \App\Domain\Account\Events\MoneyAdded $event
     *
     * @return \App\Domain\Account\Aggregates\TransactionAggregate
     */
    public function applyMoneyAdded( MoneyAdded $event ): static
    {
        $this->validateHash(
            hash: $event->hash,
            money: $event->money
        );

        $this->balance += $event->money->getAmount();

        $this->storeHash( $event->hash );

        return $this;
    }

    /**
     * @param \App\Domain\Account\DataObjects\Money $money
     *
     * @return \App\Domain\Account\Aggregates\TransactionAggregate
     */
    public function debit( Money $money ): static
    {
        if ( !$this->hasSufficientFundsToSubtractAmount( $money ) )
        {
            $this->recordThat(
                new AccountLimitHit()
            );

            $this->persist();

            throw new NotEnoughFunds;
        }

        $this->recordThat(
            new MoneySubtracted(
                money: $money,
                hash: $this->generateHash( $money )
            )
        );

        return $this;
    }

    /**
     * @param \App\Domain\Account\Events\MoneySubtracted $event
     *
     * @return \App\Domain\Account\Aggregates\TransactionAggregate
     */
    public function applyMoneySubtracted( MoneySubtracted $event ): static
    {
        $this->validateHash(
            hash: $event->hash,
            money: $event->money
        );

        $this->balance -= $event->money->getAmount();

        $this->storeHash( $event->hash );

        return $this;
    }

    /**
     * @param \App\Domain\Account\DataObjects\Money $money
     *
     * @return bool
     */
    protected function hasSufficientFundsToSubtractAmount( Money $money ): bool
    {
        return $this->balance - $money->getAmount() >= $this->accountLimit;
    }
}
