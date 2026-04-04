<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Services;

use App\Domain\Account\Models\BlockchainAddress;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Syncs Solana user addresses with Alchemy webhook monitoring via Notify API.
 *
 * Alchemy's PATCH endpoint supports atomic add/remove operations, eliminating
 * the need for read-modify-write patterns with locking (unlike Helius).
 *
 * API: PATCH https://dashboard.alchemy.com/api/update-webhook-addresses
 * Auth: X-Alchemy-Token header
 * Idempotent — safe to call multiple times with the same addresses.
 */
class AlchemyWebhookSyncService
{
    private const API_URL = 'https://dashboard.alchemy.com/api/update-webhook-addresses';

    /** @var array<string> Solana system/program addresses that must never be monitored */
    private const RESERVED_ADDRESSES = [
        '11111111111111111111111111111111',
        '11111111111111111111111111111112',
        'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA',
        'ATokenGPvbdGVxr1b2hvZbsiqW5xWH25efTNsLJA8knL',
        'SysvarC1ock11111111111111111111111111111111',
    ];

    /**
     * Add a Solana address to the Alchemy webhook.
     */
    public function addAddress(string $address): bool
    {
        if ($this->isReservedAddress($address)) {
            Log::warning('Alchemy: Rejected reserved Solana address', ['address' => $address]);

            return false;
        }

        $token = $this->getNotifyToken();
        $webhookId = $this->getWebhookId();

        if ($token === '' || $webhookId === '') {
            Log::debug('Alchemy: Webhook sync skipped — not configured');

            return false;
        }

        return $this->patchWebhookAddresses($token, $webhookId, [$address], []);
    }

    /**
     * Remove a Solana address from the Alchemy webhook.
     */
    public function removeAddress(string $address): bool
    {
        $token = $this->getNotifyToken();
        $webhookId = $this->getWebhookId();

        if ($token === '' || $webhookId === '') {
            Log::debug('Alchemy: Webhook sync skipped — not configured');

            return false;
        }

        return $this->patchWebhookAddresses($token, $webhookId, [], [$address]);
    }

    /**
     * Sync all active Solana addresses from the database to Alchemy.
     *
     * Queries all active Solana BlockchainAddress records and adds them
     * in a single PATCH call. Returns the count of synced addresses.
     */
    public function syncAllAddresses(): int
    {
        $token = $this->getNotifyToken();
        $webhookId = $this->getWebhookId();

        if ($token === '' || $webhookId === '') {
            Log::warning('Alchemy: Cannot sync — notify_token or solana_webhook_id not set');

            return 0;
        }

        $addresses = BlockchainAddress::where('chain', 'solana')
            ->where('is_active', true)
            ->pluck('address')
            ->unique()
            ->reject(fn (string $addr): bool => $this->isReservedAddress($addr))
            ->values()
            ->all();

        if ($addresses === []) {
            Log::info('Alchemy: No Solana addresses to sync');

            return 0;
        }

        $success = $this->patchWebhookAddresses($token, $webhookId, $addresses, []);

        if ($success) {
            Log::info('Alchemy: Synced all Solana addresses', ['count' => count($addresses)]);
        }

        return $success ? count($addresses) : 0;
    }

    /**
     * Send a PATCH request to Alchemy's update-webhook-addresses endpoint.
     *
     * @param array<string> $addressesToAdd
     * @param array<string> $addressesToRemove
     */
    private function patchWebhookAddresses(
        string $token,
        string $webhookId,
        array $addressesToAdd,
        array $addressesToRemove,
    ): bool {
        $response = Http::timeout(15)
            ->withHeaders([
                'X-Alchemy-Token' => $token,
            ])
            ->patch(self::API_URL, [
                'webhook_id'          => $webhookId,
                'addresses_to_add'    => array_values($addressesToAdd),
                'addresses_to_remove' => array_values($addressesToRemove),
            ]);

        if (! $response->successful()) {
            Log::error('Alchemy: Failed to update webhook addresses', [
                'status'    => $response->status(),
                'body'      => $response->body(),
                'add_count' => count($addressesToAdd),
                'rm_count'  => count($addressesToRemove),
            ]);

            return false;
        }

        return true;
    }

    private function isReservedAddress(string $address): bool
    {
        return in_array($address, self::RESERVED_ADDRESSES, true);
    }

    private function getNotifyToken(): string
    {
        return (string) config('services.alchemy.notify_token', '');
    }

    private function getWebhookId(): string
    {
        return (string) config('services.alchemy.solana_webhook_id', '');
    }
}
