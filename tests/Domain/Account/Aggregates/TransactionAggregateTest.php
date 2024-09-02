<?php

namespace Tests\Domain\Account\Aggregates;

use App\Domain\Account\Aggregates\LedgerAggregate;
use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\DataObjects\Account;
use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Events\AccountCreated;
use App\Domain\Account\Events\AccountDeleted;
use App\Domain\Account\Events\AccountLimitHit;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use App\Domain\Account\Exceptions\InvalidHashException;
use App\Domain\Account\Exceptions\NotEnoughFunds;
use App\Domain\Account\Utils\ValidatesHash;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class TransactionAggregateTest extends TestCase
{
    use ValidatesHash;

    private const string ACCOUNT_UUID = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

    private const string ACCOUNT_NAME = 'fake-account';

    /** @test */
    public function can_add_money(): void
    {
        TransactionAggregate::fake( self::ACCOUNT_UUID )
                            ->given( [
                                new AccountCreated( $this->fakeAccount() ),
                            ] )
                            ->when(
                                function ( TransactionAggregate $transactions
                                ): void {
                                    $transactions->credit(
                                        $this->money( 10 )
                                    );
                                }
                            )
                            ->assertRecorded( [
                                new MoneyAdded(
                                    $money = $this->money( 10 ),
                                    $this->generateHash( $money )
                                ),
                            ] );
    }

    /** @test */
    public function can_subtract_money(): void
    {
        $added_money = $this->money( 10 );
        $added_hash = $this->generateHash( $added_money );
        $this->resetHash( $added_hash->getHash() );

        TransactionAggregate::fake( self::ACCOUNT_UUID )
                            ->given( [
                                new AccountCreated( $this->fakeAccount() ),
                                new MoneyAdded(
                                    $added_money,
                                    $added_hash
                                ),
                            ] )
                            ->when(
                                function ( TransactionAggregate $transactions
                                ): void {
                                    $transactions->debit(
                                        $this->money( 10 )
                                    );
                                }
                            )
                            ->assertRecorded( [
                                new MoneySubtracted(
                                    $subtracted_money = $this->money( 10 ),
                                    $this->generateHash( $subtracted_money )
                                ),
                            ] )
                            ->assertNotRecorded( AccountLimitHit::class );
    }

    /** @test */
    public function cannot_subtract_money_when_money_below_account_limit(): void
    {
        TransactionAggregate::fake( self::ACCOUNT_UUID )
                            ->given( [
                                new AccountCreated( $this->fakeAccount() ),
                            ] )
                            ->when(
                                function ( TransactionAggregate $transactions
                                ): void {
                                    $this->assertExceptionThrown(
                                        function () use ( $transactions ) {
                                            $transactions->debit(
                                                $this->money( 1 )
                                            );

                                        }, NotEnoughFunds::class
                                    );
                                }
                            )
                            ->assertApplied( [
                                new AccountCreated( $this->fakeAccount() ),
                                new AccountLimitHit(),
                            ] )
                            ->assertNotRecorded( MoneySubtracted::class );

    }

    /** @test */
    public function throws_exception_on_invalid_hash(): void
    {
        $initialMoney = $this->money( 10 );
        $validHash = $this->generateHash( $initialMoney );
        $this->resetHash( $validHash->getHash() );

        TransactionAggregate::fake( self::ACCOUNT_UUID )
                            ->given( [
                                new AccountCreated( $this->fakeAccount() ),
                                new MoneyAdded(
                                    $initialMoney,
                                    $validHash
                                ),
                            ] )
                            ->when(
                                function ( TransactionAggregate $transactions
                                ): void {
                                    $this->assertExceptionThrown(
                                        function () use ( $transactions ) {
                                            $transactions->applyMoneyAdded(
                                                new MoneyAdded(
                                                    $this->money( 10 ),
                                                    $this->hash(
                                                        'invalid-hash'
                                                    ),
                                                )
                                            );
                                        }, InvalidHashException::class
                                    );
                                }
                            );
    }

    /** @test */
    public function cannot_record_event_with_invalid_hash(): void
    {
        $initialMoney = $this->money( 10 );
        $validHash = $this->generateHash( $initialMoney );
        $this->resetHash( $validHash->getHash() );

        TransactionAggregate::fake( self::ACCOUNT_UUID )
                            ->given( [
                                new AccountCreated( $this->fakeAccount() ),
                                new MoneyAdded(
                                    $initialMoney,
                                    $validHash
                                ),
                            ] )
                            ->when(
                                function ( TransactionAggregate $transactions
                                ): void {
                                    $this->assertExceptionThrown(
                                        function () use ( $transactions ) {
                                            $transactions->recordThat(
                                                new MoneyAdded(
                                                    $this->money( 10 ),
                                                    $this->hash(
                                                        'invalid-hash'
                                                    ),
                                                )
                                            );
                                        }, InvalidHashException::class
                                    );
                                }
                            );
    }


    /**
     * @return \App\Domain\Account\DataObjects\Account
     */
    protected function fakeAccount(): Account
    {
        return hydrate(
            Account::class,
            [
                'name' => self::ACCOUNT_NAME,
                'user_uuid' => $this->business_user->uuid,
            ]
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

    /**
     * @param string|null $hash
     *
     * @return \App\Domain\Account\DataObjects\Hash
     */
    private function hash( ?string $hash = '' ): Hash
    {
        return hydrate(
            Hash::class, [ 'hash' => hash( self::HASH_ALGORITHM, $hash ) ]
        );
    }
}
