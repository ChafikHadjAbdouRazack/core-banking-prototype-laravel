<?php

namespace App\Domain\Account\Projectors;

use App\Domain\Account\Actions\CreateAccount;
use App\Domain\Account\Actions\CreditAccount;
use App\Domain\Account\Actions\DebitAccount;
use App\Domain\Account\Actions\DeleteAccount;
use App\Domain\Account\Events\AccountCreated;
use App\Domain\Account\Events\AccountDeleted;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class AccountProjector extends Projector implements ShouldQueue
{
    /**
     * @param AccountCreated $event
     *
     * @return void
     */
    public function onAccountCreated(AccountCreated $event): void
    {
        app( CreateAccount::class )($event);
    }

    /**
     * @param MoneyAdded $event
     *
     * @return void
     */
    public function onMoneyAdded(MoneyAdded $event): void
    {
        app( CreditAccount::class )($event);
    }

    /**
     * @param MoneySubtracted $event
     *
     * @return void
     */
    public function onMoneySubtracted(MoneySubtracted $event): void
    {
        app( DebitAccount::class )($event);
    }

    /**
     * @param AccountDeleted $event
     *
     * @return void
     */
    public function onAccountDeleted(AccountDeleted $event): void
    {
        app( DeleteAccount::class )($event);
    }
}
