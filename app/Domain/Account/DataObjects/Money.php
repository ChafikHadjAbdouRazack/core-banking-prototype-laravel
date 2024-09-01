<?php

declare(strict_types=1);

namespace App\Domain\Account\DataObjects;

use JustSteveKing\DataObjects\Contracts\DataObjectContract;

final readonly class Money extends DataObject implements DataObjectContract
{
    /**
     * @param int $amount
     */
    public function __construct(
        private int $amount
    ) {}

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return int[]
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
        ];
    }
}
