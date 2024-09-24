<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\Money;

interface HasMoney
{
    /**
     * @return \App\Domain\Account\DataObjects\Money
     */
    public function getMoney() : Money;
}
