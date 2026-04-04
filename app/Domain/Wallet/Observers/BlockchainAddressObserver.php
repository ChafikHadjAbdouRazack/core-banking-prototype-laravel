<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Observers;

use App\Domain\Account\Models\BlockchainAddress;
use App\Domain\Wallet\Services\AlchemyWebhookSyncService;
use App\Domain\Wallet\Services\HeliusWebhookSyncService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Observes BlockchainAddress model events to sync Solana
 * addresses with external webhook providers (Helius or Alchemy).
 *
 * Queued via afterCommit to avoid blocking user registration
 * and to ensure the address is persisted before sync.
 */
class BlockchainAddressObserver
{
    public function __construct(
        private readonly HeliusWebhookSyncService $heliusSync,
        private readonly AlchemyWebhookSyncService $alchemySync,
    ) {
    }

    public function created(BlockchainAddress $address): void
    {
        if ($address->chain !== 'solana' || ! $address->is_active) {
            return;
        }

        // Dispatch async to avoid blocking registration with webhook HTTP call
        dispatch(function () use ($address): void {
            try {
                $this->getSyncService()->addAddress($address->address);
            } catch (Throwable $e) {
                Log::error('Solana webhook: Failed to sync new Solana address', [
                    'address'  => $address->address,
                    'provider' => config('services.solana_webhook_provider', 'helius'),
                    'error'    => $e->getMessage(),
                ]);
            }
        })->afterCommit();
    }

    public function deleted(BlockchainAddress $address): void
    {
        if ($address->chain !== 'solana') {
            return;
        }

        dispatch(function () use ($address): void {
            try {
                $this->getSyncService()->removeAddress($address->address);
            } catch (Throwable $e) {
                Log::error('Solana webhook: Failed to remove Solana address', [
                    'address'  => $address->address,
                    'provider' => config('services.solana_webhook_provider', 'helius'),
                    'error'    => $e->getMessage(),
                ]);
            }
        })->afterCommit();
    }

    private function getSyncService(): AlchemyWebhookSyncService|HeliusWebhookSyncService
    {
        return match (config('services.solana_webhook_provider', 'helius')) {
            'alchemy' => $this->alchemySync,
            default   => $this->heliusSync,
        };
    }
}
