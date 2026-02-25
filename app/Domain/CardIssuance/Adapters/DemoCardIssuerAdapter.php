<?php

declare(strict_types=1);

namespace App\Domain\CardIssuance\Adapters;

use App\Domain\CardIssuance\Contracts\CardIssuerInterface;
use App\Domain\CardIssuance\Enums\CardNetwork;
use App\Domain\CardIssuance\Enums\CardStatus;
use App\Domain\CardIssuance\Enums\WalletType;
use App\Domain\CardIssuance\ValueObjects\ProvisioningData;
use App\Domain\CardIssuance\ValueObjects\VirtualCard;
use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Demo implementation of card issuer for development and testing.
 */
class DemoCardIssuerAdapter implements CardIssuerInterface
{
    public function getName(): string
    {
        return 'demo';
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function createCard(
        string $userId,
        string $cardholderName,
        array $metadata = [],
        ?CardNetwork $network = null,
        ?string $label = null,
    ): VirtualCard {
        $cardToken = 'card_demo_' . bin2hex(random_bytes(16));
        $last4 = (string) random_int(1000, 9999);
        $expiresAt = (new DateTimeImmutable())->modify('+3 years');

        $card = new VirtualCard(
            cardToken: $cardToken,
            last4: $last4,
            network: $network ?? CardNetwork::VISA,
            status: CardStatus::ACTIVE,
            cardholderName: $cardholderName,
            expiresAt: $expiresAt,
            metadata: array_merge($metadata, ['user_id' => $userId, 'label' => $label]),
            label: $label,
        );

        // Store in cache for demo purposes
        Cache::put("card:{$cardToken}", $card, now()->addDays(30));
        Cache::put("user_cards:{$userId}", array_merge(
            Cache::get("user_cards:{$userId}", []),
            [$cardToken]
        ), now()->addDays(30));

        return $card;
    }

    /**
     * @param array<string> $certificates
     */
    public function getProvisioningData(
        string $cardToken,
        WalletType $walletType,
        string $deviceId,
        array $certificates = []
    ): ProvisioningData {
        // Demo provisioning data - in production, this would come from the card issuer
        return new ProvisioningData(
            cardId: $cardToken,
            walletType: $walletType,
            encryptedPassData: base64_encode("demo_encrypted_pass_data_{$cardToken}"),
            activationData: base64_encode("demo_activation_data_{$deviceId}"),
            ephemeralPublicKey: base64_encode('demo_ephemeral_key_' . bin2hex(random_bytes(32))),
            certificateChain: [
                'demo_certificate_leaf',
                'demo_certificate_intermediate',
                'demo_certificate_root',
            ],
        );
    }

    public function freezeCard(string $cardToken): bool
    {
        $card = $this->getCard($cardToken);
        if ($card === null) {
            return false;
        }

        $frozenCard = new VirtualCard(
            cardToken: $card->cardToken,
            last4: $card->last4,
            network: $card->network,
            status: CardStatus::FROZEN,
            cardholderName: $card->cardholderName,
            expiresAt: $card->expiresAt,
            metadata: $card->metadata,
            label: $card->label,
        );

        Cache::put("card:{$cardToken}", $frozenCard, now()->addDays(30));

        return true;
    }

    public function unfreezeCard(string $cardToken): bool
    {
        $card = $this->getCard($cardToken);
        if ($card === null || $card->status !== CardStatus::FROZEN) {
            return false;
        }

        $activeCard = new VirtualCard(
            cardToken: $card->cardToken,
            last4: $card->last4,
            network: $card->network,
            status: CardStatus::ACTIVE,
            cardholderName: $card->cardholderName,
            expiresAt: $card->expiresAt,
            metadata: $card->metadata,
            label: $card->label,
        );

        Cache::put("card:{$cardToken}", $activeCard, now()->addDays(30));

        return true;
    }

    public function cancelCard(string $cardToken, string $reason): bool
    {
        $card = $this->getCard($cardToken);
        if ($card === null) {
            return false;
        }

        $cancelledCard = new VirtualCard(
            cardToken: $card->cardToken,
            last4: $card->last4,
            network: $card->network,
            status: CardStatus::CANCELLED,
            cardholderName: $card->cardholderName,
            expiresAt: $card->expiresAt,
            metadata: array_merge($card->metadata, ['cancellation_reason' => $reason]),
            label: $card->label,
        );

        Cache::put("card:{$cardToken}", $cancelledCard, now()->addDays(30));

        return true;
    }

    public function getCard(string $cardToken): ?VirtualCard
    {
        return Cache::get("card:{$cardToken}");
    }
}
