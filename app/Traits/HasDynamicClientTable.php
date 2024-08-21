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

        return $this->user_uuid ?? auth()->user()->uuid;
    }
}
