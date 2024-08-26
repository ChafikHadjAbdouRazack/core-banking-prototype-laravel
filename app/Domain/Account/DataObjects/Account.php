<?php

declare(strict_types=1);

namespace App\Domain\Account\DataObjects;

use JustSteveKing\DataObjects\Contracts\DataObjectContract;

final readonly class Account implements DataObjectContract
{
    /**
     * @param string $name
     * @param int $user_id
     */
    public function __construct(
        private string $name,
        private int    $user_id
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
        return $this->user_id;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'   => $this->name,
            'userId' => $this->user_id,
        ];
    }
}
