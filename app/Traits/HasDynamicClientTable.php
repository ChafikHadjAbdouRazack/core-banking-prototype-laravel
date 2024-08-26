<?php

namespace App\Traits;

trait HasDynamicClientTable
{

    /**
     * @param string|null $customerId
     *
     * @return mixed
     */
    protected function getCustomerId(string $customerId = null): string
    {
        if ($customerId) {
            return $customerId;
        }

        if ($this->uuid)
        {
            return $this->uuid;
        }

        if ($user = auth()->user())
        {
            return $user->uuid;
        }

        return '';
    }
}
