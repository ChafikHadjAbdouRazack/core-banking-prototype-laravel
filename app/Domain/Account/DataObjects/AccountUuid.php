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
    ) {}

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
     * @return \App\Domain\Account\DataObjects\AccountUuid
     */
    public function withUuid(string $uuid): self
    {
        return new self(
            uuid: $uuid,
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'uuid'      => $this->uuid,
        ];
    }
}
