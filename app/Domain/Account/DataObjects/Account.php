<?php

declare(strict_types=1);

namespace App\Domain\Account\DataObjects;

use JustSteveKing\DataObjects\Contracts\DataObjectContract;

final readonly class Account implements DataObjectContract
{
    /**
     * @param string $name
     * @param string $user_uuid
     * @param string|null $uuid
     */
    public function __construct(
        private string  $name,
        private string  $user_uuid,
        private ?string $uuid = null
    ) {}

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function userUuid(): string
    {
        return $this->user_uuid;
    }

    /**
     * @return string|null
     */
    public function uuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return \App\Domain\Account\DataObjects\Account
     */
    public function withUuid(string $uuid): self
    {
        return new self(
            name: $this->name,
            user_uuid: $this->user_uuid,
            uuid: $uuid,
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'      => $this->name,
            'user_uuid' => $this->user_uuid,
            'uuid'      => $this->uuid,
        ];
    }
}
