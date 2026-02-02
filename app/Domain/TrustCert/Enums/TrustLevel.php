<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Enums;

/**
 * Trust levels for issuers and credentials.
 */
enum TrustLevel: string
{
    case UNKNOWN = 'unknown';
    case BASIC = 'basic';
    case VERIFIED = 'verified';
    case HIGH = 'high';
    case ULTIMATE = 'ultimate';

    public function label(): string
    {
        return match ($this) {
            self::UNKNOWN  => 'Unknown',
            self::BASIC    => 'Basic',
            self::VERIFIED => 'Verified',
            self::HIGH     => 'High',
            self::ULTIMATE => 'Ultimate',
        };
    }

    public function numericValue(): int
    {
        return match ($this) {
            self::UNKNOWN  => 0,
            self::BASIC    => 1,
            self::VERIFIED => 2,
            self::HIGH     => 3,
            self::ULTIMATE => 4,
        };
    }

    public function meetsMinimum(TrustLevel $required): bool
    {
        return $this->numericValue() >= $required->numericValue();
    }

    /**
     * Get trust requirements for this level.
     *
     * @return array<string, mixed>
     */
    public function requirements(): array
    {
        return match ($this) {
            self::UNKNOWN  => [],
            self::BASIC    => ['email_verified' => true],
            self::VERIFIED => ['email_verified' => true, 'identity_verified' => true],
            self::HIGH     => ['email_verified' => true, 'identity_verified' => true, 'kyc_completed' => true],
            self::ULTIMATE => ['email_verified' => true, 'identity_verified' => true, 'kyc_completed' => true, 'audit_completed' => true],
        };
    }
}
