<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;

trait HashValidatorProvider
{
    /**
     * @param \App\Domain\Account\DataObjects\Money $money
     * @param \App\Domain\Account\DataObjects\Hash $hash
     */
    public function __construct(
        public readonly Money $money,
        public readonly Hash $hash,
    ) {
    }

    /**
     * @return \App\Domain\Account\DataObjects\Hash
     */
    public function getHash(): Hash
    {
        return $this->hash;
    }

    /**
     * @return \App\Domain\Account\DataObjects\Money
     */
    public function getMoney(): Money
    {
        return $this->money;
    }
}
