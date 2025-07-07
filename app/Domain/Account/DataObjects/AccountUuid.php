<?php

declare(strict_types=1);

namespace App\Domain\Account\DataObjects;

use JustSteveKing\DataObjects\Contracts\DataObjectContract;

final readonly class AccountUuid extends DataObject implements DataObjectContract
{
    /**
     * @param string $uuid
     */
    public function __construct(
        private string $uuid
    ) {
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
     * @return AccountUuid
     */
    public function withUuid(string $uuid): self
    {
        return new self(
            uuid: $uuid,
        );
    }

    /**
     * Create from string UUID.
     *
     * @param string $uuid
     * @return AccountUuid
     */
    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    /**
     * Convert to string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->uuid;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
        ];
    }
}
