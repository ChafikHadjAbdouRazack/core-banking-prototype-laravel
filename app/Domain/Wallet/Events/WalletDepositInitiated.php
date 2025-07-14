<?php

namespace App\Domain\Wallet\Events;

use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Events\HasHash;
use App\Domain\Account\Events\HashValidatorProvider;
use App\Domain\Account\Events\HasMoney;
use App\Values\EventQueues;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class WalletDepositInitiated extends ShouldBeStored implements HasHash, HasMoney
{
    use HashValidatorProvider;

    public string $queue = EventQueues::TRANSACTIONS->value;

    /**
     * @param  string|null  $source  Source of deposit (card, bank_transfer, wire, etc.)
     * @param  array  $metadata  Additional metadata about the deposit
     */
    public function __construct(
        public readonly Money $money,
        public readonly Hash $hash,
        public readonly ?string $source = null,
        public readonly array $metadata = []
    ) {
    }
}
