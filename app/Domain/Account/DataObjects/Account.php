<?php

declare(strict_types=1);

namespace App\Domain\Account\DataObjects;

use JustSteveKing\DataObjects\Contracts\DataObjectContract;

final readonly class Account extends DataObject implements DataObjectContract
{
    /**
     * @param string $name
     * @param string $userUuid
     * @param string|null $uuid
     */
    public function __construct(
        private string $name,
        private string $userUuid,
        private ?string $uuid = null
    ) {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    /**
     * @return string|null
     */
    public function getUuid(): ?string
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
            userUuid: $this->userUuid,
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
            'user_uuid' => $this->userUuid,
            'uuid'      => $this->uuid,
        ];
    }
}
