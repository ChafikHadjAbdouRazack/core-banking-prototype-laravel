<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Enums;

/**
 * Supported blockchain networks for mobile payments (v1: Solana + Tron only).
 */
enum PaymentNetwork: string
{
    case SOLANA = 'SOLANA';
    case TRON = 'TRON';

    public function label(): string
    {
        return match ($this) {
            self::SOLANA => 'Solana',
            self::TRON   => 'Tron',
        };
    }

    public function nativeAsset(): string
    {
        return match ($this) {
            self::SOLANA => 'SOL',
            self::TRON   => 'TRX',
        };
    }

    public function averageGasCostUsd(): float
    {
        return match ($this) {
            self::SOLANA => 0.001,
            self::TRON   => 0.50,
        };
    }

    public function explorerBaseUrl(): string
    {
        return match ($this) {
            self::SOLANA => 'https://solscan.io/tx/',
            self::TRON   => 'https://tronscan.org/#/transaction/',
        };
    }

    public function explorerUrl(string $txHash): string
    {
        return $this->explorerBaseUrl() . $txHash;
    }

    public function requiredConfirmations(): int
    {
        return match ($this) {
            self::SOLANA => 32,
            self::TRON   => 20,
        };
    }

    public function addressPattern(): string
    {
        return match ($this) {
            self::SOLANA => '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/',
            self::TRON   => '/^T[1-9A-HJ-NP-Za-km-z]{33}$/',
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
