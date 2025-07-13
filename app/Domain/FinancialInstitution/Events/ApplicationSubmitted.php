<?php

namespace App\Domain\FinancialInstitution\Events;

use App\Models\FinancialInstitutionApplication;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationSubmitted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly FinancialInstitutionApplication $application
    ) {}
}
