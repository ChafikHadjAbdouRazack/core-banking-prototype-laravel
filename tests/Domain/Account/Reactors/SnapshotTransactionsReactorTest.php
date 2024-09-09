<?php

namespace Tests\Domain\Account\Reactors;

use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Events\TransactionThresholdReached;
use Tests\TestCase;

class SnapshotTransactionsReactorTest extends TestCase
{
    private const string ACCOUNT_UUID = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

    private const string ACCOUNT_NAME = 'fake-account';

    /** @test */
    public function fires_transaction_threshold_reached_event_when_threshold_is_met(
    ): void
    {
        TransactionAggregate::fake( self::ACCOUNT_UUID )
                            ->when(
                                function ( TransactionAggregate $transactions
                                ): void {
                                    for ( $i = 0; $i <=
                                                  TransactionAggregate::COUNT_THRESHOLD;
                                        $i++
                                    )
                                    {
                                        $transactions->credit(
                                            $this->money( 10 )
                                        );
                                    }
                                }
                            )
                            ->assertEventRecorded(
                                new TransactionThresholdReached()
                            );
    }

    /**
     * @param int $amount
     *
     * @return \App\Domain\Account\DataObjects\Money
     */
    private function money( int $amount ): Money
    {
        return hydrate( Money::class, [ 'amount' => $amount ] );
    }
}
