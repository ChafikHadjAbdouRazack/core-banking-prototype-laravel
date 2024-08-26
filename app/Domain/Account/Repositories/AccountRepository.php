<?php

namespace App\Domain\Account\Repositories;

use App\Models\Account;

final class AccountRepository implements Repository
{
    public function __construct()
    {}

    /**
     * @param array $data
     *
     * @return Account
     */
    public function create(array $data): Account
    {
        return Account::create($data);
    }

    /**
     * @param string $uuid
     *
     * @return Account
     */
    public function findByUuid(string $uuid): Account
    {
        return Account::where('uuid', $uuid)->first();
    }
}
