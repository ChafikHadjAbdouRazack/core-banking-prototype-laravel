<?php

namespace App\Domain\Account\Services;

use App\Domain\Account\Aggregates\LedgerAggregate;
use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\DataObjects\Account;
use App\Domain\Account\DataObjects\Money;
use App\Models\Account as AccountModel;
use Illuminate\Support\Str;

class AccountService
{
    /**
     * @param \App\Domain\Account\Aggregates\LedgerAggregate $ledger
     * @param \App\Domain\Account\Aggregates\TransactionAggregate $transaction
     */
    public function __construct(
        protected LedgerAggregate      $ledger,
        protected TransactionAggregate $transaction
    ) {
    }

    /**
     * @param mixed $account
     *
     * @return string
     */
    public function create( Account|array $account ): string
    {
        $uuid = Str::uuid();

        $this->ledger->retrieve( $uuid )
                     ->createAccount( self::getAccount( $account ) )
                     ->persist();

        return $uuid;
    }

    /**
     * @param mixed $uuid
     *
     * @return void
     */
    public function destroy( mixed $uuid ): void
    {
        $this->ledger->retrieve( self::getUuid( $uuid ) )
                     ->deleteAccount()
                     ->persist();
    }

    /**
     * @param mixed $uuid
     * @param mixed $amount
     *
     * @return void
     */
    public function deposit( mixed $uuid, mixed $amount ): void
    {
        $this->transaction->retrieve( self::getUuid( $uuid ) )
                          ->credit( self::getMoney( $amount ) )
                          ->persist();
    }

    /**
     * @param mixed $uuid
     * @param mixed $amount
     *
     * @return void
     */
    public function withdraw( mixed $uuid, mixed $amount ): void
    {
        $this->transaction->retrieve( self::getUuid( $uuid ) )
                          ->debit( self::getMoney( $amount ) )
                          ->persist();
    }

    /**
     * @param mixed $from
     * @param mixed $to
     * @param mixed $amount
     *
     * @return void
     */
    public function transfer( mixed $from, mixed $to, mixed $amount ): void
    {
        $debiting = $this->transaction->loadUuid( self::getUuid( $from ) )
                                      ->debit( self::getMoney( $amount ) );

        $crediting = $this->transaction->loadUuid( self::getUuid( $to ) )
                                       ->credit( self::getMoney( $amount ) );

        $debiting->persist();
        $crediting->persist();
    }

    /**
     * @param \App\Domain\Account\DataObjects\Account|\App\Models\Account|string $uuid
     *
     * @return string
     */
    protected static function getUuid( Account|AccountModel|string $uuid ): string {
        if ( $uuid instanceof Account )
        {
            return $uuid->uuid();
        }

        if ( $uuid instanceof AccountModel )
        {
            return $uuid->uuid;
        }

        return $uuid;
    }

    /**
     * @param \App\Domain\Account\DataObjects\Money|int $amount
     *
     * @return \App\Domain\Account\DataObjects\Money
     */
    protected static function getMoney( Money|int $amount ): Money
    {
        if ( $amount instanceof Money )
        {
            return $amount;
        }

        return hydrate(
            class: Money::class,
            properties: [
                'amount' => $amount,
            ]
        );
    }

    /**
     * @param Account|array $account
     *
     * @return Account
     */
    protected function getAccount( Account|array $account ): Account
    {
        if ( $account instanceof Account )
        {
            return $account;
        }

        return hydrate(
            class: Account::class,
            properties: $account
        );
    }
}
