<?php

namespace App\Domain\Account\Aggregates;

use App\Domain\Account\DataObjects\Account;
use App\Domain\Account\Events\AccountCreated;
use App\Domain\Account\Events\AccountDeleted;
use App\Domain\Account\Events\AccountFrozen;
use App\Domain\Account\Events\AccountUnfrozen;
use App\Domain\Account\Repositories\LedgerRepository;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;
use \App\Domain\Account\Repositories\LedgerSnapshotRepository;

class LedgerAggregate extends AggregateRoot
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
    
    /**
     * @param string $reason
     * @param string|null $authorizedBy
     *
     * @return $this
     */
    public function freezeAccount(string $reason, ?string $authorizedBy = null): static
    {
        $this->recordThat(
            domainEvent: new AccountFrozen(
                reason: $reason,
                authorizedBy: $authorizedBy
            )
        );

        return $this;
    }
    
    /**
     * @param string $reason
     * @param string|null $authorizedBy
     *
     * @return $this
     */
    public function unfreezeAccount(string $reason, ?string $authorizedBy = null): static
    {
        $this->recordThat(
            domainEvent: new AccountUnfrozen(
                reason: $reason,
                authorizedBy: $authorizedBy
            )
        );

        return $this;
    }
}
