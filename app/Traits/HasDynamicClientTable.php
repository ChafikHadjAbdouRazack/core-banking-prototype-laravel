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

        if ($this->user()) {
            return $this->user->uuid;
        }

        return auth()->user()->uuid;
    }
}
