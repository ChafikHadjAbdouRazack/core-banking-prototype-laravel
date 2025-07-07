<?php

namespace App\Domain\Wallet\Contracts;

interface KeyManagementServiceInterface
{
    /**
     * Generate a new mnemonic phrase
     */
    public function generateMnemonic(): string;

    /**
     * Derive a key pair from a mnemonic and path
     */
    public function deriveKeyPair(string $mnemonic, string $path): array;

    /**
     * Encrypt sensitive data
     */
    public function encrypt(string $data): string;

    /**
     * Decrypt encrypted data
     */
    public function decrypt(string $encryptedData): string;

    /**
     * Generate a secure random key
     */
    public function generateKey(): string;

    /**
     * Sign data with a private key
     */
    public function sign(string $data, string $privateKey): string;

    /**
     * Verify a signature
     */
    public function verify(string $data, string $signature, string $publicKey): bool;
}