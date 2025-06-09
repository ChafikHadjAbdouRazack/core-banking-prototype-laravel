<?php

namespace Tests\Domain\Account\Projectors;

use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Repositories\TurnoverRepository;
use App\Domain\Account\Utils\ValidatesHash;
use App\Models\Turnover;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TurnoverProjectorTest extends TestCase
{
    use ValidatesHash;

    #[Test]
    public function test_calculate_today_turnover(): void
    {
        $this->resetHash();
        $date = Carbon::createFromDate(2024, 1, 1);
        Carbon::setTestNow($date);

        $turnover = $this->getTurnoverForDate($date);
        $amount = $turnover->amount ?? 0;
        $count = $turnover->count ?? 0;

        $this->performTransactions([
            ['credit', 10],
            ['debit', 5],
            ['credit', 10],
        ]);

        $turnover = $this->getTurnoverForDate($date);

        $this->assertEquals($count + 3, $turnover->count);
        $this->assertEquals($amount + 15, $turnover->amount);

        Carbon::setTestNow();
    }

    #[Test]
    public function test_calculate_tomorrow_turnover(): void
    {
        $this->resetHash();
        $date = Carbon::createFromDate(2024, 1, 1);
        Carbon::setTestNow($date);

        // Simulate yesterday's event
        $this->performTransactions([
            ['credit', 10],
        ]);

        $date = Carbon::createFromDate(2024, 1, 2);
        Carbon::setTestNow($date);

        $turnover = $this->getTurnoverForDate($date);
        $amount = $turnover->amount ?? 0;
        $count = $turnover->count ?? 0;

        $this->performTransactions([
            ['credit', 10],
        ]);

        $turnover = $this->getTurnoverForDate($date);

        $this->assertEquals($count + 1, $turnover->count);
        $this->assertEquals($amount + 10, $turnover->amount);

        Carbon::setTestNow();
    }

    /**
     * @param \Illuminate\Support\Carbon $date
     *
     * @return \App\Models\Turnover|null
     */
    private function getTurnoverForDate( Carbon $date): ?Turnover
    {
        return app(TurnoverRepository::class)->findByAccountAndDate($this->account, $date);
    }

    /**
     * @param array $transactions
     *
     * @return void
     */
    private function performTransactions( array $transactions): void
    {
        $aggregate = TransactionAggregate::retrieve($this->account->uuid);
        foreach ($transactions as [$type, $amount]) {
            $aggregate->$type($this->money($amount));
        }
        $aggregate->persist();
    }

    /**
     * @param int $amount
     *
     * @return \App\Domain\Account\DataObjects\Money
     */
    private function money( int $amount): Money
    {
        return hydrate(Money::class, ['amount' => $amount]);
    }
}
