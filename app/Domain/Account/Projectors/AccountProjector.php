<?php

namespace App\Domain\Account\Projectors;

use App\Domain\Account\Repositories\AccountRepository;
use App\Domain\Account\Events\AccountCreated;
use App\Domain\Account\Events\AccountDeleted;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class AccountProjector extends Projector
{
    public function __construct(
        protected AccountRepository $accountRepository
    )
    {
    }

    /**
     * @param AccountCreated $event
     *
     * @return void
     */
    public function onAccountCreated(AccountCreated $event): void
    {
        $this->accountRepository->create(
            $event->account->withUuid(
                $event->aggregateRootUuid()
            )
        );
    }

    /**
     * @param MoneyAdded $event
     *
     * @return void
     */
    public function onMoneyAdded(MoneyAdded $event): void
    {
        $this->accountRepository->findByUuid(
            $event->aggregateRootUuid()
        )->addMoney(
            $event->money->amount()
        );
    }

    /**
     * @param MoneySubtracted $event
     *
     * @return void
     */
    public function onMoneySubtracted(MoneySubtracted $event): void
    {
        $this->accountRepository->findByUuid(
            $event->aggregateRootUuid()
        )->subtractMoney(
            $event->money->amount()
        );
    }

    /**
     * @param AccountDeleted $event
     *
     * @return void
     */
    public function onAccountDeleted(AccountDeleted $event): void
    {
        $this->accountRepository->findByUuid(
            $event->aggregateRootUuid()
        )->delete();
    }
}
