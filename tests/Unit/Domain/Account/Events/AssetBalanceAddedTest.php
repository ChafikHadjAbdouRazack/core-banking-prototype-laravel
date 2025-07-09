<?php

namespace Tests\Unit\Domain\Account\Events;

use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\Events\AssetBalanceAdded;
use App\Values\EventQueues;
use Tests\TestCase;

class AssetBalanceAddedTest extends TestCase
{
    public function test_creates_event_with_required_properties(): void
    {
        $hash = Hash::fromData('test-hash-value');

        $event = new AssetBalanceAdded(
            assetCode: 'USD',
            amount: 1500,
            hash: $hash
        );

        $this->assertEquals('USD', $event->assetCode);
        $this->assertEquals(1500, $event->amount);
        $this->assertSame($hash, $event->hash);
        $this->assertEquals([], $event->metadata);
        $this->assertEquals(EventQueues::TRANSACTIONS->value, $event->queue);
    }

    public function test_creates_event_with_metadata(): void
    {
        $hash = Hash::fromData('test-hash-with-metadata');
        $metadata = [
            'source'    => 'bank_transfer',
            'reference' => 'TX123456',
            'timestamp' => '2024-01-15T10:30:00Z',
        ];

        $event = new AssetBalanceAdded(
            assetCode: 'EUR',
            amount: 2500,
            hash: $hash,
            metadata: $metadata
        );

        $this->assertEquals($metadata, $event->metadata);
    }

    public function test_get_amount_returns_correct_value(): void
    {
        $event = new AssetBalanceAdded(
            assetCode: 'BTC',
            amount: 100000000, // 1 BTC in satoshi
            hash: Hash::fromData('btc-hash')
        );

        $this->assertEquals(100000000, $event->getAmount());
    }

    public function test_get_asset_code_returns_correct_value(): void
    {
        $event = new AssetBalanceAdded(
            assetCode: 'ETH',
            amount: 1000000000000000000, // 1 ETH in wei
            hash: Hash::fromData('eth-hash')
        );

        $this->assertEquals('ETH', $event->getAssetCode());
    }

    public function test_handles_zero_amount(): void
    {
        $event = new AssetBalanceAdded(
            assetCode: 'USDT',
            amount: 0,
            hash: Hash::fromData('zero-amount-hash')
        );

        $this->assertEquals(0, $event->amount);
        $this->assertEquals(0, $event->getAmount());
    }

    public function test_stores_different_asset_codes(): void
    {
        $assets = ['USD', 'EUR', 'GBP', 'JPY', 'CHF', 'BTC', 'ETH', 'USDT'];

        foreach ($assets as $assetCode) {
            $event = new AssetBalanceAdded(
                assetCode: $assetCode,
                amount: 1000,
                hash: Hash::fromData("hash-{$assetCode}")
            );

            $this->assertEquals($assetCode, $event->assetCode);
        }
    }

    public function test_event_is_immutable(): void
    {
        $event = new AssetBalanceAdded(
            assetCode: 'USD',
            amount: 1000,
            hash: Hash::fromData('immutable-hash')
        );

        // Properties are readonly, so we can't modify them
        $this->expectError();
        $event->assetCode = 'EUR';
    }
}
