<?php

namespace App\Domain\Account\Utils;

use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Events\HasHash;
use App\Domain\Account\Events\HasMoney;
use App\Domain\Account\Exceptions\InvalidHashException;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

trait ValidatesHash
{
    private const string HASH_ALGORITHM = 'sha256';

    public string $currentHash = '';

    /**
     * @param \App\Domain\Account\DataObjects\Money|null $money
     *
     * @return \App\Domain\Account\DataObjects\Hash
     */
    protected function generateHash( ?Money $money = null ): Hash
    {
        return hydrate(
            Hash::class,
            [
                'hash' => hash(
                    self::HASH_ALGORITHM,
                    $this->currentHash . ( $money ? $money->getAmount() : 0 )
                ),
            ]
        );
    }

    /**
     * @param \App\Domain\Account\DataObjects\Hash $hash
     * @param \App\Domain\Account\DataObjects\Money|null $money
     *
     * @return void
     */
    protected function validateHash( Hash $hash, ?Money $money = null ): void
    {
        if ( !$this->generateHash( money: $money )->equals( $hash ) )
        {
            throw new InvalidHashException();
        }
    }

    /**
     * @param Hash $hash
     *
     * @return void
     */
    protected function storeHash( Hash $hash ): void
    {
        $this->currentHash = $hash->getHash();
    }

    /**
     * @param string|null $hash
     *
     * @return void
     */
    protected function resetHash( ?string $hash = null ): void
    {
        $this->currentHash = $hash ?? '';
    }
}
