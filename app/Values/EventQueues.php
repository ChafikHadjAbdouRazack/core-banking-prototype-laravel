<?php

namespace App\Values;

enum EventQueues: string
{
    case EVENTS = 'events';
    case LEDGER = 'ledger';
    case TRANSACTIONS = 'transactions';
    case TRANSFERS = 'transfers';
    case LIQUIDITY_POOLS = 'liquidity_pools';

    /**
     * @return self
     */
    public static function default(): self
    {
        return self::EVENTS;
    }
}
