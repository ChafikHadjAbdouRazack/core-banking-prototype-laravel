<?php

namespace App\Domain\Account\Services;

use App\Domain\Account\DataObjects\Account;
use App\Domain\Account\Events\AccountCreated;
use App\Domain\Account\Events\AccountDeleted;
use App\Domain\Account\Repositories\LedgerRepository;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;
use \App\Domain\Account\Repositories\LedgerSnapshotRepository;

class LedgerService extends AggregateRoot
{
    /**
     * @return LedgerRepository
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getStoredEventRepository(): LedgerRepository
    {
        return app()->make(
            abstract: LedgerRepository::class
        );
    }

    /**
     * @return LedgerSnapshotRepository
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getSnapshotRepository(): LedgerSnapshotRepository
    {
        return app()->make(
            abstract: LedgerSnapshotRepository::class
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
     * @return $this
     */
    public function deleteAccount(): static
    {
        $this->recordThat(
            domainEvent: new AccountDeleted()
        );

        return $this;
    }
}
