<?php

namespace Tests\Unit\Domain\Account\Aggregates;

use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Events\AccountLimitHit;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use App\Domain\Account\Events\TransactionThresholdReached;
use App\Domain\Account\Exceptions\NotEnoughFunds;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionAggregateTest extends TestCase
{
    use RefreshDatabase;

    public function test_credits_money_to_account(): void
    {
        $aggregate = TransactionAggregate::fake();
        $money = new Money(1000); // $10.00

        $aggregate->credit($money);

        $aggregate->assertRecorded([
            new MoneyAdded(
                money: $money,
                hash: $aggregate->generateHash($money)
            ),
        ]);
    }

    public function test_debits_money_from_account(): void
    {
        $aggregate = TransactionAggregate::fake();

        // First add money
        $creditMoney = new Money(5000); // $50.00
        $aggregate->credit($creditMoney);

        // Then debit
        $debitMoney = new Money(2000); // $20.00
        $aggregate->debit($debitMoney);

        $aggregate->assertRecorded([
            new MoneyAdded(
                money: $creditMoney,
                hash: $aggregate->generateHash($creditMoney)
            ),
            new MoneySubtracted(
                money: $debitMoney,
                hash: $aggregate->generateHash($debitMoney)
            ),
        ]);
    }

    public function test_throws_exception_when_insufficient_funds(): void
    {
        $aggregate = TransactionAggregate::fake();

        // Add small amount
        $aggregate->credit(new Money(100)); // $1.00

        $this->expectException(NotEnoughFunds::class);

        // Try to debit more
        $aggregate->debit(new Money(200)); // $2.00
    }

    public function test_applies_money_added_event(): void
    {
        $aggregate = new TransactionAggregate();
        $money = new Money(2500); // €25.00

        $event = new MoneyAdded(
            money: $money,
            hash: $aggregate->generateHash($money)
        );

        $aggregate->applyMoneyAdded($event);

        $this->assertEquals(2500, $aggregate->balance);
        $this->assertEquals(1, $aggregate->count);
    }

    public function test_applies_money_subtracted_event(): void
    {
        $aggregate = new TransactionAggregate(balance: 5000); // Start with $50.00
        $money = new Money(1500); // $15.00

        $event = new MoneySubtracted(
            money: $money,
            hash: $aggregate->generateHash($money)
        );

        $aggregate->applyMoneySubtracted($event);

        $this->assertEquals(3500, $aggregate->balance);
        $this->assertEquals(1, $aggregate->count);
    }

    public function test_records_transaction_threshold_reached(): void
    {
        $aggregate = TransactionAggregate::fake();
        $money = new Money(100);

        // Make COUNT_THRESHOLD transactions
        for ($i = 0; $i < TransactionAggregate::COUNT_THRESHOLD; $i++) {
            $aggregate->credit($money);
        }

        // Verify threshold event was recorded
        $aggregate->assertRecorded(function (TransactionThresholdReached $event) {
            return true;
        });
    }

    public function test_resets_count_after_threshold(): void
    {
        $aggregate = new TransactionAggregate();
        $money = new Money(100);

        // Set count just below threshold
        $aggregate->count = TransactionAggregate::COUNT_THRESHOLD - 1;

        $event = new MoneyAdded(
            money: $money,
            hash: $aggregate->generateHash($money)
        );

        $aggregate->applyMoneyAdded($event);

        // Count should be reset to 0 after hitting threshold
        $this->assertEquals(0, $aggregate->count);
    }

    public function test_records_account_limit_hit_on_debit(): void
    {
        $aggregate = TransactionAggregate::fake();

        // Set balance to exactly the limit (0)
        $aggregate->balance = 0;

        // Try to debit when at limit
        $money = new Money(100);

        try {
            $aggregate->debit($money);
        } catch (NotEnoughFunds $e) {
            // Expected
        }

        $aggregate->assertRecorded(function (AccountLimitHit $event) {
            return true;
        });
    }

    public function test_validates_hash_for_duplicate_prevention(): void
    {
        $aggregate = new TransactionAggregate();
        $money = new Money(1000);
        $hash = $aggregate->generateHash($money);

        // Store hash first
        $aggregate->storeHash($hash);

        // Try to use same hash again
        $this->expectException(\Exception::class);
        $aggregate->validateHash($hash, $money);
    }

    public function test_handles_different_currencies(): void
    {
        $aggregate = TransactionAggregate::fake();

        $usdMoney = new Money(1000);
        $eurMoney = new Money(2000);

        $aggregate->credit($usdMoney);
        $aggregate->credit($eurMoney);

        // Both transactions should be recorded
        $aggregate->assertRecordedCount(2);
    }

    public function test_maintains_balance_across_multiple_operations(): void
    {
        $aggregate = new TransactionAggregate();

        // Credit operations
        $aggregate->applyMoneyAdded(new MoneyAdded(
            money: new Money(1000),
            hash: 'hash1'
        ));

        $aggregate->applyMoneyAdded(new MoneyAdded(
            money: new Money(2000),
            hash: 'hash2'
        ));

        // Debit operation
        $aggregate->applyMoneySubtracted(new MoneySubtracted(
            money: new Money(500),
            hash: 'hash3'
        ));

        // Final balance: 1000 + 2000 - 500 = 2500
        $this->assertEquals(2500, $aggregate->balance);
        $this->assertEquals(3, $aggregate->count);
    }

    public function test_snapshot_preserves_state(): void
    {
        $aggregate = new TransactionAggregate(balance: 10000, count: 500);

        // Take snapshot
        $snapshot = $aggregate->getState();

        // Create new aggregate from snapshot
        $newAggregate = TransactionAggregate::fromSnapshot($snapshot);

        $this->assertEquals(10000, $newAggregate->balance);
        $this->assertEquals(500, $newAggregate->count);
    }
}
