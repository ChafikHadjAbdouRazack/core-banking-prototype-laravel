<?php

namespace Tests\Unit\Domain\Account\Aggregates;

use App\Domain\Account\Aggregates\TransferAggregate;
use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Events\MoneyTransferred;
use App\Domain\Account\Events\TransferThresholdReached;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferAggregateTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfers_money_between_accounts(): void
    {
        $aggregate = TransferAggregate::fake();

        $from = new AccountUuid('from-account-uuid');
        $to = new AccountUuid('to-account-uuid');
        $money = new Money(2500); // $25.00

        $aggregate->transfer($from, $to, $money);

        // Assert that a MoneyTransferred event was recorded
        $aggregate->assertRecorded(function (MoneyTransferred $event) use ($from, $to, $money) {
            return $event->from->toString() === $from->toString() &&
                   $event->to->toString() === $to->toString() &&
                   $event->money->getAmount() === $money->getAmount();
        });
    }

    public function test_applies_money_transferred_event(): void
    {
        $aggregate = new TransferAggregate();

        $from = new AccountUuid('sender-uuid');
        $to = new AccountUuid('receiver-uuid');
        $money = new Money(5000); // €50.00

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($aggregate);
        $method = $reflection->getMethod('generateHash');
        $method->setAccessible(true);
        $hash = $method->invoke($aggregate, $money);

        $event = new MoneyTransferred(
            from: $from,
            to: $to,
            money: $money,
            hash: $hash
        );

        $aggregate->applyMoneyTransferred($event);

        $this->assertEquals(1, $aggregate->count);
    }

    public function test_records_transfer_threshold_reached(): void
    {
        $aggregate = TransferAggregate::fake();

        $from = new AccountUuid('from-uuid');
        $to = new AccountUuid('to-uuid');
        $money = new Money(100);

        // Make COUNT_THRESHOLD transfers
        for ($i = 0; $i < TransferAggregate::COUNT_THRESHOLD; $i++) {
            $aggregate->transfer($from, $to, $money);
        }

        // Verify threshold event was recorded
        $aggregate->assertRecorded(function (TransferThresholdReached $event) {
            return true;
        });
    }

    public function test_resets_count_after_threshold(): void
    {
        $aggregate = new TransferAggregate();

        // Set count just below threshold
        $aggregate->count = TransferAggregate::COUNT_THRESHOLD - 1;

        $event = new MoneyTransferred(
            from: new AccountUuid('from'),
            to: new AccountUuid('to'),
            money: new Money(100),
            hash: 'unique-hash'
        );

        $aggregate->applyMoneyTransferred($event);

        // Count should be reset to 0 after hitting threshold
        $this->assertEquals(0, $aggregate->count);
    }

    public function test_validates_hash_for_duplicate_prevention(): void
    {
        $aggregate = new TransferAggregate();
        $money = new Money(1000);

        // Use reflection to access protected methods
        $reflection = new \ReflectionClass($aggregate);

        $generateMethod = $reflection->getMethod('generateHash');
        $generateMethod->setAccessible(true);
        $hash = $generateMethod->invoke($aggregate, $money);

        $storeMethod = $reflection->getMethod('storeHash');
        $storeMethod->setAccessible(true);
        $storeMethod->invoke($aggregate, $hash);

        // Try to use same hash again
        $this->expectException(\Exception::class);

        $validateMethod = $reflection->getMethod('validateHash');
        $validateMethod->setAccessible(true);
        $validateMethod->invoke($aggregate, $hash, $money);
    }

    public function test_handles_different_currency_transfers(): void
    {
        $aggregate = TransferAggregate::fake();

        $from = new AccountUuid('multi-currency-from');
        $to = new AccountUuid('multi-currency-to');

        // Transfer USD
        $aggregate->transfer($from, $to, new Money(1000));

        // Transfer EUR
        $aggregate->transfer($from, $to, new Money(2000));

        // Transfer BTC
        $aggregate->transfer($from, $to, new Money(10000000)); // 0.1 BTC

        // All transfers should be recorded
        $aggregate->assertRecordedCount(3);
    }

    public function test_maintains_count_across_multiple_transfers(): void
    {
        $aggregate = new TransferAggregate();

        $from = new AccountUuid('counter-from');
        $to = new AccountUuid('counter-to');

        // Apply multiple transfer events
        for ($i = 1; $i <= 5; $i++) {
            $event = new MoneyTransferred(
                from: $from,
                to: $to,
                money: new Money($i * 100),
                hash: "hash-{$i}"
            );
            $aggregate->applyMoneyTransferred($event);
        }

        $this->assertEquals(5, $aggregate->count);
    }

    public function test_handles_large_transfer_amounts(): void
    {
        $aggregate = TransferAggregate::fake();

        $from = new AccountUuid('large-from');
        $to = new AccountUuid('large-to');
        $money = new Money(100000000); // $1,000,000.00

        $aggregate->transfer($from, $to, $money);

        $aggregate->assertRecorded(function (MoneyTransferred $event) {
            return $event->money->getAmount() === 100000000;
        });
    }

    public function test_snapshot_preserves_transfer_count(): void
    {
        $aggregate = new TransferAggregate(count: 750);

        // Take snapshot
        $snapshot = $aggregate->getState();

        // Create new aggregate from snapshot
        $newAggregate = TransferAggregate::fromSnapshot($snapshot);

        $this->assertEquals(750, $newAggregate->count);
    }

    public function test_transfer_between_same_account_allowed(): void
    {
        $aggregate = TransferAggregate::fake();

        $account = new AccountUuid('self-transfer-account');
        $money = new Money(500);

        // Transfer from account to itself (edge case)
        $aggregate->transfer($account, $account, $money);

        $aggregate->assertRecorded(function (MoneyTransferred $event) use ($account) {
            return $event->from->toString() === $account->toString()
                && $event->to->toString() === $account->toString();
        });
    }
}
