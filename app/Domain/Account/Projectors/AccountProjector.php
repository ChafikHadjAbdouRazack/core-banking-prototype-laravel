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
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class AccountProjector extends Projector
{
    protected array $handlesEvents = [
        AccountCreated::class => CreateAccount::class,
        MoneyAdded::class => CreditAccount::class,
        MoneySubtracted::class => DebitAccount::class,
        AccountDeleted::class => DeleteAccount::class,
    ];
}
