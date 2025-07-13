<?php

namespace App\Models;

use App\Domain\Payment\Models\PaymentDeposit as BasePaymentDeposit;

/**
 * @deprecated Use App\Domain\Payment\Models\PaymentDeposit instead
 */
class PaymentDeposit extends BasePaymentDeposit
{
    // This is a facade/proxy class for backward compatibility
    // All functionality is inherited from the domain model
}
