<?php

declare(strict_types=1);

namespace App\Domain\Account\DataObjects;

use InvalidArgumentException;
use JustSteveKing\DataObjects\Contracts\DataObjectContract;

final readonly class Hash extends DataObject implements DataObjectContract
{
    /**
     * @param string $hash
     */
    public function __construct(
        private string $hash
    ) {
        if (! $this->isValidHash($hash)) {
            throw new InvalidArgumentException(
                message: 'Invalid hash hash provided.'
            );
        }
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Validate the hash format.
     *
     * @param string $hash
     *
     * @return bool
     */
    private function isValidHash(string $hash): bool
    {
        // SHA3-512 produces a 128-character hexadecimal string
        return ctype_xdigit($hash) && strlen($hash) === 128; // SHA3-512 length
    }

    /**
     * @param Hash $hash
     *
     * @return bool
     */
    public function equals(self $hash): bool
    {
        return hash_equals($this->hash, $hash->getHash());
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
        ];
    }
}
