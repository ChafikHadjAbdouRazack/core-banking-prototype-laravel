<?php

namespace Tests\Unit\Testing\Support;

use Carbon\Carbon;

class EventWithCarbonDate
{
    public string $title;

    public Carbon $createdAt;

    public ?Carbon $updatedAt;
}
