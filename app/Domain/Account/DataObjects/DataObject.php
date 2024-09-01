<?php

namespace App\Domain\Account\DataObjects;

use JustSteveKing\DataObjects\Contracts\DataObjectContract;

abstract readonly class DataObject implements DataObjectContract
{
    /**
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * @param array $params
     *
     * @return self
     */
    public static function fromArray(array $params): self
    {
        return hydrate(
            class: static::class,
            properties: $params
        );
    }
}
