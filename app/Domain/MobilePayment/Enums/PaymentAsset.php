<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Enums;

/**
 * Supported assets for mobile payments (v1: USDC only).
 */
enum PaymentAsset: string
{
    case USDC = 'USDC';

    public function label(): string
    {
        return match ($this) {
            self::USDC => 'USD Coin',
        };
    }

    public function decimals(): int
    {
        return match ($this) {
            self::USDC => 6,
        };
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
