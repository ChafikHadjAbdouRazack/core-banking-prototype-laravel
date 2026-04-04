<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handle HyperSwitch payment lifecycle webhooks.
 *
 * HyperSwitch sends webhook events for payment status changes:
 * payment_succeeded, payment_failed, payment_processing,
 * refund_succeeded, refund_failed, dispute_opened, etc.
 *
 * Setup in HyperSwitch dashboard or via API:
 *   webhook_url: https://zelta.app/api/webhooks/hyperswitch
 */
class HyperSwitchWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            Log::warning('HyperSwitch: Webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $eventType = (string) $request->input('event_type', '');
        $paymentId = (string) $request->input('content.object.payment_id', '');

        Log::info('HyperSwitch: Webhook received', [
            'event_type' => $eventType,
            'payment_id' => $paymentId,
        ]);

        match ($eventType) {
            'payment_succeeded'  => $this->handlePaymentSucceeded($request->all()),
            'payment_failed'     => $this->handlePaymentFailed($request->all()),
            'payment_processing' => $this->handlePaymentProcessing($request->all()),
            'refund_succeeded'   => $this->handleRefundSucceeded($request->all()),
            'refund_failed'      => $this->handleRefundFailed($request->all()),
            'dispute_opened'     => $this->handleDisputeOpened($request->all()),
            default              => Log::debug('HyperSwitch: Unhandled event type', ['event_type' => $eventType]),
        };

        return response()->json(['received' => true]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function handlePaymentSucceeded(array $payload): void
    {
        $paymentId = $payload['content']['object']['payment_id'] ?? '';
        $amount = $payload['content']['object']['amount'] ?? 0;
        $connector = $payload['content']['object']['connector'] ?? '';

        Log::info('HyperSwitch: Payment succeeded', [
            'payment_id' => $paymentId,
            'amount'     => $amount,
            'connector'  => $connector,
        ]);

        // Domain event dispatch would go here once deposit workflows integrate HyperSwitch
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function handlePaymentFailed(array $payload): void
    {
        $paymentId = $payload['content']['object']['payment_id'] ?? '';
        $error = $payload['content']['object']['error_message'] ?? '';

        Log::warning('HyperSwitch: Payment failed', [
            'payment_id' => $paymentId,
            'error'      => $error,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function handlePaymentProcessing(array $payload): void
    {
        Log::info('HyperSwitch: Payment processing', [
            'payment_id' => $payload['content']['object']['payment_id'] ?? '',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function handleRefundSucceeded(array $payload): void
    {
        Log::info('HyperSwitch: Refund succeeded', [
            'refund_id'  => $payload['content']['object']['refund_id'] ?? '',
            'payment_id' => $payload['content']['object']['payment_id'] ?? '',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function handleRefundFailed(array $payload): void
    {
        Log::warning('HyperSwitch: Refund failed', [
            'refund_id' => $payload['content']['object']['refund_id'] ?? '',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function handleDisputeOpened(array $payload): void
    {
        Log::warning('HyperSwitch: Dispute opened', [
            'payment_id' => $payload['content']['object']['payment_id'] ?? '',
        ]);
    }

    /**
     * Verify the webhook signature.
     *
     * HyperSwitch signs webhooks using HMAC-SHA512 over the raw body.
     */
    private function verifySignature(Request $request): bool
    {
        $secret = (string) config('hyperswitch.webhook_secret', '');

        if ($secret === '') {
            if (app()->environment('production')) {
                Log::error('HyperSwitch: HYPERSWITCH_WEBHOOK_SECRET not set in production');

                return false;
            }

            return true;
        }

        $signature = $request->header('x-webhook-signature-512', '');

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        $computed = hash_hmac('sha512', $request->getContent(), $secret);

        return hash_equals($computed, $signature);
    }
}
