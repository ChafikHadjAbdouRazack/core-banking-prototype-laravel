<?php

declare(strict_types=1);

use App\Domain\MobilePayment\Enums\PaymentNetwork;

describe('PaymentNetwork Enum', function (): void {
    it('has only Solana and Tron for v1', function (): void {
        $networks = PaymentNetwork::cases();

        expect($networks)->toHaveCount(2);
        expect(PaymentNetwork::SOLANA->value)->toBe('SOLANA');
        expect(PaymentNetwork::TRON->value)->toBe('TRON');
    });

    it('returns correct labels', function (): void {
        expect(PaymentNetwork::SOLANA->label())->toBe('Solana');
        expect(PaymentNetwork::TRON->label())->toBe('Tron');
    });

    it('returns correct native assets', function (): void {
        expect(PaymentNetwork::SOLANA->nativeAsset())->toBe('SOL');
        expect(PaymentNetwork::TRON->nativeAsset())->toBe('TRX');
    });

    it('returns explorer URLs', function (): void {
        $url = PaymentNetwork::SOLANA->explorerUrl('abc123');
        expect($url)->toBe('https://solscan.io/tx/abc123');

        $url = PaymentNetwork::TRON->explorerUrl('def456');
        expect($url)->toBe('https://tronscan.org/#/transaction/def456');
    });

    it('returns required confirmations', function (): void {
        expect(PaymentNetwork::SOLANA->requiredConfirmations())->toBeGreaterThan(0);
        expect(PaymentNetwork::TRON->requiredConfirmations())->toBeGreaterThan(0);
    });

    it('returns address patterns', function (): void {
        expect(PaymentNetwork::SOLANA->addressPattern())->toBeString();
        expect(PaymentNetwork::TRON->addressPattern())->toBeString();
    });

    it('returns all values as array', function (): void {
        $values = PaymentNetwork::values();
        expect($values)->toBe(['SOLANA', 'TRON']);
    });

    it('returns average gas cost', function (): void {
        expect(PaymentNetwork::SOLANA->averageGasCostUsd())->toBeFloat();
        expect(PaymentNetwork::TRON->averageGasCostUsd())->toBeFloat();
    });
});
