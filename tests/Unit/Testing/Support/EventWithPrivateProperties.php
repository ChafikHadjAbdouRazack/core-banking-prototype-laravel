<?php

namespace Tests\Unit\Testing\Support;

class EventWithPrivateProperties
{
    private string $privateData;

    public string $publicData;

    public function getPrivateData(): string
    {
        return $this->privateData;
    }
}
