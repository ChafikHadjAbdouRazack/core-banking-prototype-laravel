<?php

declare(strict_types=1);

namespace App\Domain\Asset\Events;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class AssetTransferCompleted extends ShouldBeStored
{
    public function __construct(
        public readonly AccountUuid $fromAccountUuid,
        public readonly AccountUuid $toAccountUuid,
        public readonly string $fromAssetCode,
        public readonly string $toAssetCode,
        public readonly Money $fromAmount,
        public readonly Money $toAmount,
        public readonly Hash $hash,
        public readonly ?string $transferId = null,
        public readonly ?array $metadata = null
    ) {}

    /**
     * Check if this is a same-asset transfer.
     */
    public function isSameAssetTransfer(): bool
    {
        return $this->fromAssetCode === $this->toAssetCode;
    }

    /**
     * Check if this is a cross-asset transfer (exchange).
     */
    public function isCrossAssetTransfer(): bool
    {
        return $this->fromAssetCode !== $this->toAssetCode;
    }
}
