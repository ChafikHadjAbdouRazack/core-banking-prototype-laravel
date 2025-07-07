<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\Hash;

interface HasHash
{
    /**
     * @return Hash
     */
    public function getHash(): Hash;
}
