<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Services;

use App\Domain\MobilePayment\Enums\PaymentAsset;
use App\Domain\MobilePayment\Enums\PaymentNetwork;
use App\Domain\Wallet\Factories\BlockchainConnectorFactory;

/**
 * Provides receive addresses for mobile wallet users.
 *
 * Generates or retrieves deposit addresses for supported network+asset combinations.
 */
class ReceiveAddressService
{
    /**
     * Get a receive address for the given user, network, and asset.
     *
     * @return array{address: string, qr_payload: string, network: string, asset: string, warning: string, minimum_amount: string}
     */
    public function getReceiveAddress(int $userId, PaymentNetwork $network, PaymentAsset $asset): array
    {
        $connector = BlockchainConnectorFactory::create(strtolower($network->value));

        // Generate deterministic address from user ID as seed
        $publicKey = $this->derivePublicKey($userId, $network);
        $addressData = $connector->generateAddress($publicKey);

        $address = $addressData->address;

        return [
            'address'        => $address,
            'qr_payload'     => $this->buildQrValue($address, $network, $asset),
            'network'        => $network->value,
            'asset'          => $asset->value,
            'warning'        => "Only send {$asset->value} on {$network->label()} to this address. Using other tokens or networks may result in loss.",
            'minimum_amount' => '0.01',
        ];
    }

    /**
     * Derive a deterministic public key for the user and network.
     * In production, this would use the user's actual wallet/key hierarchy.
     */
    private function derivePublicKey(int $userId, PaymentNetwork $network): string
    {
        if (config('mobile_payment.demo_mode', false)) {
            return $this->generateDemoAddress($userId, $network);
        }

        // In production, derive from HD wallet path per user
        $seed = hash('sha256', "finaegis:{$userId}:{$network->value}");

        return $seed;
    }

    private function generateDemoAddress(int $userId, PaymentNetwork $network): string
    {
        $hash = hash('sha256', "demo:{$userId}:{$network->value}");

        return match ($network) {
            PaymentNetwork::SOLANA => $this->toBase58Like($hash),
            PaymentNetwork::TRON   => 'T' . substr(strtoupper($hash), 0, 33),
            PaymentNetwork::POLYGON, PaymentNetwork::BASE, PaymentNetwork::ARBITRUM, PaymentNetwork::ETHEREUM => '0x' . substr($hash, 0, 40),
        };
    }

    /**
     * Build a QR-encodable value for the address.
     */
    private function buildQrValue(string $address, PaymentNetwork $network, PaymentAsset $asset): string
    {
        return match ($network) {
            PaymentNetwork::SOLANA => "solana:{$address}?spl-token=USDC",
            PaymentNetwork::TRON   => $address,
            PaymentNetwork::POLYGON, PaymentNetwork::BASE, PaymentNetwork::ARBITRUM, PaymentNetwork::ETHEREUM => "ethereum:{$address}",
        };
    }

    /**
     * Generate a base58-like string from hex hash for demo Solana addresses.
     */
    private function toBase58Like(string $hex): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $result = '';

        for ($i = 0; $i < 44 && $i < strlen($hex); $i++) {
            $charCode = ord($hex[$i]);
            $result .= $alphabet[$charCode % strlen($alphabet)];
        }

        return $result;
    }
}
