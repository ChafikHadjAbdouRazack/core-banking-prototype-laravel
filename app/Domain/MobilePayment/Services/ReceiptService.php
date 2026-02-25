<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Services;

use App\Domain\MobilePayment\Enums\PaymentIntentStatus;
use App\Domain\MobilePayment\Models\PaymentIntent;
use App\Domain\MobilePayment\Models\PaymentReceipt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Service for generating and retrieving payment receipts.
 *
 * Receipts are generated on-demand for confirmed payment intents and
 * cached in Redis for the configured TTL (default 24h).
 */
class ReceiptService
{
    /**
     * Generate a receipt for a confirmed payment intent.
     *
     * Returns an existing receipt if one was already generated for this intent.
     */
    public function generateReceipt(string $txId, int $userId): ?PaymentReceipt
    {
        // Look up the activity feed item or payment intent by txId
        $intent = PaymentIntent::where('public_id', $txId)
            ->where('user_id', $userId)
            ->first();

        if (! $intent) {
            return null;
        }

        if ($intent->status !== PaymentIntentStatus::CONFIRMED) {
            return null;
        }

        // Use firstOrCreate to prevent TOCTOU race condition on duplicate receipts
        $receipt = PaymentReceipt::firstOrCreate(
            [
                'payment_intent_id' => $intent->id,
                'user_id'           => $userId,
            ],
            [
                'public_id'      => 'rcpt_' . Str::random(24),
                'merchant_name'  => $intent->merchant->display_name ?? 'Unknown Merchant',
                'amount'         => $intent->amount,
                'asset'          => $intent->asset,
                'network'        => $intent->network,
                'tx_hash'        => $intent->tx_hash,
                'network_fee'    => $this->formatNetworkFee($intent),
                'share_token'    => Str::random(64),
                'transaction_at' => $intent->confirmed_at ?? $intent->created_at,
            ],
        );

        // Cache if newly created
        if ($receipt->wasRecentlyCreated) {
            $cacheHours = (int) config('mobile_payment.receipt_cache_hours', 24);
            Cache::put(
                "receipt:{$receipt->public_id}",
                $receipt->toApiResponse(),
                now()->addHours($cacheHours)
            );
        }

        return $receipt;
    }

    /**
     * Get a receipt by its public ID.
     */
    public function getReceipt(string $receiptId, int $userId): ?PaymentReceipt
    {
        return PaymentReceipt::where('public_id', $receiptId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get a receipt by its share token (public, no auth required).
     */
    public function getReceiptByShareToken(string $shareToken): ?PaymentReceipt
    {
        return PaymentReceipt::where('share_token', $shareToken)->first();
    }

    /**
     * Format the network fee from the payment intent's fee estimate.
     */
    private function formatNetworkFee(PaymentIntent $intent): string
    {
        $fees = $intent->fees_estimate;

        if (! $fees || ! isset($fees['usdApprox'])) {
            return '0.01 USD';
        }

        return $fees['usdApprox'] . ' USD';
    }
}
