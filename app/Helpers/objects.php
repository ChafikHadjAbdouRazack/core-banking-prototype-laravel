<?php

use App\Domain\Account\DataObjects\Account;
use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Models\Account as AccountModel;
use JustSteveKing\DataObjects\Contracts\DataObjectContract;
use JustSteveKing\DataObjects\Facades\Hydrator;

if (!function_exists('hydrate') )
{
    /**
     * Hydrate and return a specific Data Object class instance.
     * @template T of DataObjectContract
     *
     * @param class-string<T> $class
     * @param array $properties
     *
     * @return T
     */
	function hydrate(string $class, array $properties): DataObjectContract
	{
        return Hydrator::fill(
            class: $class,
            properties: collect($properties)->map(function($value) {
                return $value instanceof UnitEnum ? $value->value : $value;
            })->toArray()
        );
    }
}

if (!function_exists('__account') )
{
    /**
     * @param Account|array $account
     *
     * @return Account
     */
    function __account( Account|array $account ): Account
    {
        if ( $account instanceof Account )
        {
            return $account;
        }

        return hydrate(
            class: Account::class,
            properties: $account
        );
    }
}

if (!function_exists('__money') )
{
    /**
     * @param Money|int $amount
     *
     * @return Money
     */
    function __money( Money|int $amount ): Money
    {
        if ( $amount instanceof Money )
        {
            return $amount;
        }

        return hydrate(
            class: Money::class,
            properties: [
                'amount' => $amount,
            ]
        );
    }
}

if (!function_exists('__account_uuid') )
{
    /**
     * @param \App\Domain\Account\DataObjects\Account|\App\Models\Account|string $uuid
     *
     * @return AccountUuid
     */
    function __account_uuid( Account|AccountModel|string $uuid ): AccountUuid
    {
        if ( $uuid instanceof Account )
        {
            $uuid = $uuid->uuid();
        }

        if ( $uuid instanceof AccountModel )
        {
            $uuid = $uuid->uuid;
        }

        return hydrate(
            class: AccountUuid::class,
            properties: [
                'uuid' => $uuid,
            ]
        );
    }
}

if (!function_exists('__account__uuid') )
{
    /**
     * @param \App\Domain\Account\DataObjects\Account|\App\Models\Account|string $uuid
     *
     * @return string
     */
    function __account__uuid( Account|AccountModel|string $uuid ): string
    {
        if ( $uuid instanceof Account )
        {
            return $uuid->uuid();
        }

        if ( $uuid instanceof AccountModel )
        {
            return $uuid->uuid;
        }

        return $uuid;
    }
}
