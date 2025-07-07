<?php

namespace App\Domain\Account\Events;

use App\Domain\Account\DataObjects\Hash;
use App\Values\EventQueues;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class AssetBalanceSubtracted extends ShouldBeStored implements HasHash
{
    use HashValidatorProvider;

    /**
     * @var string
     */
    public string $queue = EventQueues::TRANSACTIONS->value;

    /**
     * @param string $assetCode
     * @param int $amount
     * @param Hash $hash
     * @param array|null $metadata
     */
    public function __construct(
        public readonly string $assetCode,
        public readonly int $amount,
        public readonly Hash $hash,
        public readonly ?array $metadata = []
    ) {
    }

    /**
     * Get the amount for this event.
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Get the asset code for this event.
     */
    public function getAssetCode(): string
    {
        return $this->assetCode;
    }
}
