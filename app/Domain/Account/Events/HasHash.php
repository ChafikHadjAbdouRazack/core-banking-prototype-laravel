<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\Hash;

interface HasHash
{
    /**
     * @return \App\Domain\Account\DataObjects\Hash
     */
    public function getHash(): Hash;
}
