<?php

declare(strict_types=1);

namespace App\Domain\Account\DataObjects;

use JustSteveKing\DataObjects\Contracts\DataObjectContract;

final readonly class Account implements DataObjectContract
{
    /**
     * @param string $name
     * @param int $userId
     */
    public function __construct(
        private string $name,
        private int    $userId
    ) {}

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function userId(): int
    {
        return $this->userId;
    }

    /**
     * @return array|mixed[]
     */
    public function toArray(): array
    {
        return [
            'name'   => $this->name,
            'userId' => $this->userId,
        ];
    }
}
