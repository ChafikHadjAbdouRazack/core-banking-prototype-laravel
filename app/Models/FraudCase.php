<?php

namespace App\Models;

/**
 * FraudCase model for backward compatibility
 * This extends the Domain FraudCase model.
 */
class FraudCase extends \App\Domain\Fraud\Models\FraudCase
{
    // All functionality is inherited from the Domain model
}
