<?php

namespace App\Models;

use App\Domain\Payment\Models\PaymentWithdrawal as BasePaymentWithdrawal;

/**
 * @deprecated Use App\Domain\Payment\Models\PaymentWithdrawal instead
 */
class PaymentWithdrawal extends BasePaymentWithdrawal
{
    // This is a facade/proxy class for backward compatibility
    // All functionality is inherited from the domain model
}
