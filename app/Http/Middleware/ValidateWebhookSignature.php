<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $provider  The webhook provider (stripe, coinbase, paysera, etc.)
     */
    public function handle(Request $request, Closure $next, string $provider): Response
    {
        $isValid = match ($provider) {
            'stripe' => $this->validateStripeSignature($request),
            'coinbase' => $this->validateCoinbaseSignature($request),
            'paysera' => $this->validatePayseraSignature($request),
            'santander' => $this->validateSantanderSignature($request),
            'openbanking' => $this->validateOpenBankingSignature($request),
            default => false,
        };

        if (! $isValid) {
            Log::warning('Webhook signature validation failed', [
                'provider' => $provider,
                'url' => $request->url(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }

    /**
     * Validate Stripe webhook signature.
     */
    private function validateStripeSignature(Request $request): bool
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if (! $signature || ! $secret) {
            return false;
        }

        // Stripe uses a timestamp-based signature
        $elements = explode(',', $signature);
        $timestamp = null;
        $signatures = [];

        foreach ($elements as $element) {
            $parts = explode('=', $element, 2);
            if ($parts[0] === 't') {
                $timestamp = $parts[1];
            } elseif ($parts[0] === 'v1') {
                $signatures[] = $parts[1];
            }
        }

        if (! $timestamp || empty($signatures)) {
            return false;
        }

        // Check timestamp tolerance (5 minutes)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        // Compute expected signature
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        // Check if any of the signatures match
        foreach ($signatures as $signature) {
            if (hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate Coinbase Commerce webhook signature.
     */
    private function validateCoinbaseSignature(Request $request): bool
    {
        $payload = $request->getContent();
        $signature = $request->header('X-CC-Webhook-Signature');
        $secret = config('services.coinbase_commerce.webhook_secret');

        if (! $signature || ! $secret) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate Paysera webhook signature.
     */
    private function validatePayseraSignature(Request $request): bool
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Paysera-Signature');
        $secret = config('custodians.connectors.paysera.webhook_secret');

        if (! $signature || ! $secret) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate Santander webhook signature.
     */
    private function validateSantanderSignature(Request $request): bool
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Santander-Signature');
        $timestamp = $request->header('X-Santander-Timestamp');
        $secret = config('custodians.connectors.santander.webhook_secret');

        if (! $signature || ! $timestamp || ! $secret) {
            return false;
        }

        // Check timestamp tolerance (5 minutes)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        // Santander includes timestamp in signature
        $dataToSign = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha512', $dataToSign, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate Open Banking webhook signature.
     */
    private function validateOpenBankingSignature(Request $request): bool
    {
        // For Open Banking callbacks, we might use OAuth state parameter validation
        // or JWT token validation depending on the provider
        $state = $request->query('state');
        $expectedState = session('openbanking_state');

        if (! $state || ! $expectedState) {
            return false;
        }

        // Clear the state after validation to prevent replay
        session()->forget('openbanking_state');

        return hash_equals($expectedState, $state);
    }
}