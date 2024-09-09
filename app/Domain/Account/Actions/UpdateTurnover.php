<?php

namespace App\Domain\Account\Actions;

use App\Domain\Account\Events\HasMoney;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use App\Domain\Account\Repositories\TurnoverRepository;
use Illuminate\Support\Carbon;

class UpdateTurnover
{
    public function __construct(
        protected TurnoverRepository $turnoverRepository,
    ) {
    }

    /**
     * @param \App\Domain\Account\Events\HasMoney $event
     *
     * @return void
     */
    public function __invoke( HasMoney $event ): void
    {
        $amount = $event instanceof MoneySubtracted
            ? $event->money->invert()->getAmount()
            : $event->money->getAmount();

        $this->turnoverRepository->incrementForDateById(
            Carbon::now(),
            $event->aggregateRootUuid(),
            $amount
        );
    }
}
