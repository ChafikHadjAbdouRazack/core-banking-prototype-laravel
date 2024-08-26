<?php

namespace App\Domain\Account\Repositories;

interface Repository
{
    /**
     * @param array $data
     *
     * @return mixed
     */
    public function create( array $data): mixed;
}
