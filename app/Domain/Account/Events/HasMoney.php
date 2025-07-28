<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\Money;

interface HasMoney
{
    public function getMoney(): Money;
}
