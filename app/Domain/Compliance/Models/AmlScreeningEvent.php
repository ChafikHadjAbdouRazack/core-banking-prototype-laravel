<?php

/**
 * AML Screening Event Model.
 */

namespace App\Domain\Compliance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

/**
 * Model for AML screening events stored in the event sourcing table.
 */
class AmlScreeningEvent extends EloquentStoredEvent
{
    use HasFactory;

    public $table = 'aml_screening_events';
}
