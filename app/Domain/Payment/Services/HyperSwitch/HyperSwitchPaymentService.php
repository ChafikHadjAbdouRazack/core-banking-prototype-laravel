<?php

declare(strict_types=1);

namespace App\Domain\Payment\Services\HyperSwitch;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * High-level payment orchestration service via HyperSwitch.
 *
 * Wraps the HyperSwitch REST API with domain-friendly methods
 * for deposit, withdrawal, and refund operations. Handles
 * customer mapping and metadata enrichment.
 */
class HyperSwitchPaymentService
{
    public function __construct(
        private readonly HyperSwitchClient $client,
    ) {
    }

    /**
     * Initiate a card deposit via HyperSwitch.
     *
     * Routes through the best available processor based on HyperSwitch routing config.
     *
     * @return array{payment_id: string, client_secret: string, status: string, connector: string|null}
     */
    public function initiateDeposit(
        int $amountCents,
        string $currency,
        string $userUuid,
        string $userEmail,
        string $returnUrl,
        ?string $description = null,
    ): array {
        $customerId = $this->ensureCustomer($userUuid, $userEmail);

        $response = $this->client->createPayment([
            'amount'      => $amountCents,
            'currency'    => strtoupper($currency),
            'customer_id' => $customerId,
            'return_url'  => $returnUrl,
            'confirm'     => false, // Two-step: create then confirm with payment method
            'description' => $description ?? 'Deposit',
            'metadata'    => [
                'user_uuid'  => $userUuid,
                'source'     => 'finaegis',
                'created_at' => gmdate('c'),
            ],
            'statement_descriptor_name' => config('brand.name', 'Zelta'),
        ]);

        Log::info('HyperSwitch: Deposit initiated', [
            'payment_id' => $response['payment_id'] ?? null,
            'amount'     => $amountCents,
            'currency'   => $currency,
            'user'       => $userUuid,
        ]);

        return [
            'payment_id'    => (string) ($response['payment_id'] ?? ''),
            'client_secret' => (string) ($response['client_secret'] ?? ''),
            'status'        => (string) ($response['status'] ?? 'unknown'),
            'connector'     => $response['connector'] ?? null,
        ];
    }

    /**
     * Get payment status from HyperSwitch.
     *
     * @return array{status: string, amount: int, currency: string, connector: string|null, error: string|null}
     */
    public function getPaymentStatus(string $paymentId): array
    {
        $response = $this->client->getPayment($paymentId);

        return [
            'status'    => (string) ($response['status'] ?? 'unknown'),
            'amount'    => (int) ($response['amount'] ?? 0),
            'currency'  => (string) ($response['currency'] ?? ''),
            'connector' => $response['connector'] ?? null,
            'error'     => $response['error_message'] ?? null,
        ];
    }

    /**
     * Process a refund via HyperSwitch.
     *
     * @return array{refund_id: string, status: string}
     */
    public function refund(string $paymentId, int $amountCents, string $reason = ''): array
    {
        $response = $this->client->createRefund($paymentId, $amountCents, $reason);

        Log::info('HyperSwitch: Refund initiated', [
            'payment_id' => $paymentId,
            'refund_id'  => $response['refund_id'] ?? null,
            'amount'     => $amountCents,
        ]);

        return [
            'refund_id' => (string) ($response['refund_id'] ?? ''),
            'status'    => (string) ($response['status'] ?? 'unknown'),
        ];
    }

    /**
     * List available payment connectors.
     *
     * @return array<int, array{name: string, enabled: bool}>
     */
    public function listConnectors(): array
    {
        $connectors = $this->client->listConnectors();

        // API may return indexed array of connector objects or empty
        $result = [];
        foreach ($connectors as $c) {
            if (is_array($c)) {
                $result[] = [
                    'name'    => (string) ($c['connector_name'] ?? 'unknown'),
                    'enabled' => (bool) ($c['disabled'] ?? false) === false,
                ];
            }
        }

        return $result;
    }

    /**
     * Check if HyperSwitch is available and configured.
     *
     * @return array{enabled: bool, available: bool, base_url: string}
     */
    public function status(): array
    {
        $enabled = (bool) config('hyperswitch.enabled', false);

        if (! $enabled) {
            return ['enabled' => false, 'available' => false, 'base_url' => ''];
        }

        $health = $this->client->health();

        return [
            'enabled'   => true,
            'available' => (bool) ($health['available'] ?? false),
            'base_url'  => (string) config('hyperswitch.base_url', ''),
        ];
    }

    /**
     * Ensure a HyperSwitch customer exists for this user.
     */
    private function ensureCustomer(string $userUuid, string $userEmail): string
    {
        $customerId = 'hs_' . Str::slug($userUuid);

        try {
            $this->client->getCustomer($customerId);
        } catch (RuntimeException) {
            // Customer doesn't exist — create
            $this->client->createCustomer([
                'customer_id' => $customerId,
                'email'       => $userEmail,
                'metadata'    => ['finaegis_uuid' => $userUuid],
            ]);
        }

        return $customerId;
    }
}
