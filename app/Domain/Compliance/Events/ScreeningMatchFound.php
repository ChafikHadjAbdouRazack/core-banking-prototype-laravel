<?php

namespace App\Domain\Compliance\Events;

use App\Models\AmlScreening;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScreeningMatchFound
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly AmlScreening $screening
    ) {
    }
}
