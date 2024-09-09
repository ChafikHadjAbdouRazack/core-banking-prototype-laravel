<?php

namespace App\Domain\Account\Projectors;

use App\Domain\Account\Actions\UpdateTurnover;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class TurnoverProjector extends Projector
{
    public function onMoneyAdded( MoneyAdded $event ): void
    {
        app( UpdateTurnover::class )( $event );
    }

    public function onMoneySubtracted( MoneySubtracted $event ): void
    {
        app( UpdateTurnover::class )( $event );
    }
}
