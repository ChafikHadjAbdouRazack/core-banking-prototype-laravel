<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;

trait HashValidatorProvider
{
    /**
     * @param Money $money
     * @param Hash  $hash
     */
    public function __construct(
        public readonly Money $money,
        public readonly Hash $hash,
    ) {
    }

    /**
     * @return Hash
     */
    public function getHash(): Hash
    {
        return $this->hash;
    }

    /**
     * @return Money
     */
    public function getMoney(): Money
    {
        return $this->money;
    }
}
