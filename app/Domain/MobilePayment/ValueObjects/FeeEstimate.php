<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\ValueObjects;

use App\Domain\MobilePayment\Enums\PaymentNetwork;

final readonly class FeeEstimate
{
    /**
     * @param  numeric-string  $amount
     * @param  numeric-string  $usdApprox
     */
    public function __construct(
        public string $nativeAsset,
        public string $amount,
        public string $usdApprox,
    ) {
    }

    public static function forNetwork(PaymentNetwork $network): self
    {
        return match ($network) {
            PaymentNetwork::SOLANA => new self(
                nativeAsset: 'SOL',
                amount: '0.00004',
                usdApprox: '0.01',
            ),
            PaymentNetwork::TRON => new self(
                nativeAsset: 'TRX',
                amount: '5.0',
                usdApprox: '0.50',
            ),
        };
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'nativeAsset' => $this->nativeAsset,
            'amount'      => $this->amount,
            'usdApprox'   => $this->usdApprox,
        ];
    }
}
