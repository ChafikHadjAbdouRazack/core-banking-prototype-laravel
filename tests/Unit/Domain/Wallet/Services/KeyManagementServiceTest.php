<?php

namespace Tests\Unit\Domain\Wallet\Services;

use PHPUnit\Framework\Attributes\Test;
use Tests\ServiceTestCase;

class KeyManagementServiceTest extends ServiceTestCase
{
    private KeyManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KeyManagementService();
    }

    #[Test]
    public function test_generate_master_key_creates_valid_key(): void
    {
        $key = $this->service->generateMasterKey();

        $this->assertIsArray($key);
        $this->assertArrayHasKey('public_key', $key);
        $this->assertArrayHasKey('private_key', $key);
        $this->assertArrayHasKey('address', $key);
        $this->assertStringStartsWith('0x', $key['address']);
        $this->assertEquals(42, strlen($key['address'])); // Ethereum address length
    }

    #[Test]
    public function test_generate_mnemonic_creates_12_words_by_default(): void
    {
        $mnemonic = $this->service->generateMnemonic();
        $words = explode(' ', $mnemonic);

        $this->assertCount(12, $words);
        $this->assertIsString($mnemonic);
    }

    #[Test]
    public function test_generate_mnemonic_creates_24_words_when_specified(): void
    {
        $mnemonic = $this->service->generateMnemonic(24);
        $words = explode(' ', $mnemonic);

        $this->assertCount(24, $words);
    }

    #[Test]
    public function test_generate_mnemonic_throws_exception_for_invalid_word_count(): void
    {
        $this->expectException(KeyManagementException::class);
        $this->expectExceptionMessage('Word count must be 12 or 24');

        $this->service->generateMnemonic(15);
    }

    #[Test]
    public function test_derive_from_mnemonic_generates_valid_keys(): void
    {
        $mnemonic = $this->service->generateMnemonic();
        $keys = $this->service->deriveFromMnemonic($mnemonic);

        $this->assertIsArray($keys);
        $this->assertArrayHasKey('public_key', $keys);
        $this->assertArrayHasKey('private_key', $keys);
        $this->assertArrayHasKey('address', $keys);
        $this->assertArrayHasKey('mnemonic', $keys);
        $this->assertEquals($mnemonic, $keys['mnemonic']);
    }

    #[Test]
    public function test_encrypt_private_key_returns_encrypted_string(): void
    {
        $privateKey = 'test_private_key_12345';
        $encrypted = $this->service->encryptPrivateKey($privateKey);

        $this->assertIsString($encrypted);
        $this->assertNotEquals($privateKey, $encrypted);

        // Verify it can be decrypted
        $decrypted = Crypt::decryptString($encrypted);
        $this->assertEquals($privateKey, $decrypted);
    }

    #[Test]
    public function test_decrypt_private_key_returns_original_key(): void
    {
        $privateKey = 'test_private_key_12345';
        $encrypted = Crypt::encryptString($privateKey);

        $decrypted = $this->service->decryptPrivateKey($encrypted);

        $this->assertEquals($privateKey, $decrypted);
    }

    #[Test]
    public function test_decrypt_private_key_throws_exception_on_invalid_data(): void
    {
        $this->expectException(KeyManagementException::class);
        $this->expectExceptionMessage('Failed to decrypt private key');

        $this->service->decryptPrivateKey('invalid_encrypted_data');
    }

    #[Test]
    public function test_validate_mnemonic_accepts_12_words(): void
    {
        $mnemonic = implode(' ', array_fill(0, 12, 'word'));

        $result = $this->service->validateMnemonic($mnemonic);

        $this->assertTrue($result);
    }

    #[Test]
    public function test_validate_mnemonic_accepts_24_words(): void
    {
        $mnemonic = implode(' ', array_fill(0, 24, 'word'));

        $result = $this->service->validateMnemonic($mnemonic);

        $this->assertTrue($result);
    }

    #[Test]
    public function test_validate_mnemonic_rejects_invalid_word_count(): void
    {
        $mnemonic = implode(' ', array_fill(0, 15, 'word'));

        $result = $this->service->validateMnemonic($mnemonic);

        $this->assertFalse($result);
    }

    #[Test]
    public function test_generate_backup_creates_valid_backup_structure(): void
    {
        $walletId = 'wallet_123';
        $data = ['custom' => 'data'];

        $backup = $this->service->generateBackup($walletId, $data);

        $this->assertIsArray($backup);
        $this->assertArrayHasKey('backup_id', $backup);
        $this->assertArrayHasKey('wallet_id', $backup);
        $this->assertArrayHasKey('encrypted_data', $backup);
        $this->assertArrayHasKey('checksum', $backup);
        $this->assertArrayHasKey('created_at', $backup);
        $this->assertEquals($walletId, $backup['wallet_id']);
        $this->assertStringStartsWith('backup_', $backup['backup_id']);
    }

    #[Test]
    public function test_generate_backup_works_without_custom_data(): void
    {
        $walletId = 'wallet_123';

        $backup = $this->service->generateBackup($walletId);

        $this->assertIsArray($backup);
        $this->assertArrayHasKey('encrypted_data', $backup);

        // Verify the encrypted data contains an empty data array
        $decrypted = Crypt::decryptString($backup['encrypted_data']);
        $data = json_decode($decrypted, true);
        $this->assertEmpty($data['data']);
    }

    #[Test]
    public function test_restore_from_backup_restores_wallet_data(): void
    {
        $walletId = 'wallet_123';
        $customData = ['custom' => 'data'];

        // Generate a backup first
        $backup = $this->service->generateBackup($walletId, $customData);

        // Restore from backup
        $restored = $this->service->restoreFromBackup($backup);

        $this->assertIsArray($restored);
        $this->assertArrayHasKey('wallet_id', $restored);
        $this->assertArrayHasKey('version', $restored);
        $this->assertArrayHasKey('created_at', $restored);
        $this->assertArrayHasKey('addresses', $restored);
        $this->assertArrayHasKey('metadata', $restored);
        $this->assertArrayHasKey('data', $restored);
        $this->assertEquals($walletId, $restored['wallet_id']);
        $this->assertEquals($customData, $restored['data']);
    }

    #[Test]
    public function test_restore_from_backup_validates_checksum(): void
    {
        $backup = [
            'backup_id'      => 'backup_123',
            'wallet_id'      => 'wallet_123',
            'encrypted_data' => 'invalid_data',
            'checksum'       => 'invalid_checksum',
            'created_at'     => now()->toIso8601String(),
        ];

        $this->expectException(KeyManagementException::class);
        $this->expectExceptionMessage('Invalid backup checksum');

        $this->service->restoreFromBackup($backup);
    }

    #[Test]
    public function test_derive_child_key_generates_different_keys(): void
    {
        $parentKey = $this->service->generateMasterKey();

        $child1 = $this->service->deriveChildKey($parentKey['private_key'], 0);
        $child2 = $this->service->deriveChildKey($parentKey['private_key'], 1);

        $this->assertNotEquals($child1['private_key'], $child2['private_key']);
        $this->assertNotEquals($child1['public_key'], $child2['public_key']);
        $this->assertNotEquals($child1['address'], $child2['address']);
    }

    #[Test]
    public function test_derive_child_key_is_deterministic(): void
    {
        $parentKey = $this->service->generateMasterKey();
        $index = 5;

        $child1 = $this->service->deriveChildKey($parentKey['private_key'], $index);
        $child2 = $this->service->deriveChildKey($parentKey['private_key'], $index);

        $this->assertEquals($child1['private_key'], $child2['private_key']);
        $this->assertEquals($child1['public_key'], $child2['public_key']);
        $this->assertEquals($child1['address'], $child2['address']);
    }

    #[Test]
    public function test_sign_message_creates_valid_signature(): void
    {
        $keys = $this->service->generateMasterKey();
        $message = 'Test message to sign';

        $signature = $this->service->signMessage($message, $keys['private_key']);

        $this->assertIsString($signature);
        $this->assertNotEmpty($signature);
        // Ethereum signatures are typically 132 characters (0x + 130 hex chars)
        $this->assertGreaterThan(100, strlen($signature));
    }

    #[Test]
    public function test_verify_signature_validates_correct_signature(): void
    {
        $keys = $this->service->generateMasterKey();
        $message = 'Test message to sign';
        $signature = $this->service->signMessage($message, $keys['private_key']);

        $isValid = $this->service->verifySignature($message, $signature, $keys['public_key']);

        $this->assertTrue($isValid);
    }

    #[Test]
    public function test_verify_signature_rejects_invalid_signature(): void
    {
        $keys = $this->service->generateMasterKey();
        $message = 'Test message to sign';
        $invalidSignature = 'invalid_signature_data';

        $isValid = $this->service->verifySignature($message, $invalidSignature, $keys['public_key']);

        $this->assertFalse($isValid);
    }

    #[Test]
    public function test_verify_signature_rejects_wrong_message(): void
    {
        $keys = $this->service->generateMasterKey();
        $originalMessage = 'Original message';
        $signature = $this->service->signMessage($originalMessage, $keys['private_key']);

        $isValid = $this->service->verifySignature('Different message', $signature, $keys['public_key']);

        $this->assertFalse($isValid);
    }

    #[Test]
    public function test_generate_deterministic_key_creates_consistent_keys(): void
    {
        $seed = 'test_seed_12345';

        $key1 = $this->service->generateDeterministicKey($seed);
        $key2 = $this->service->generateDeterministicKey($seed);

        $this->assertEquals($key1['private_key'], $key2['private_key']);
        $this->assertEquals($key1['public_key'], $key2['public_key']);
        $this->assertEquals($key1['address'], $key2['address']);
    }

    #[Test]
    public function test_generate_deterministic_key_creates_different_keys_for_different_seeds(): void
    {
        $key1 = $this->service->generateDeterministicKey('seed1');
        $key2 = $this->service->generateDeterministicKey('seed2');

        $this->assertNotEquals($key1['private_key'], $key2['private_key']);
        $this->assertNotEquals($key1['public_key'], $key2['public_key']);
        $this->assertNotEquals($key1['address'], $key2['address']);
    }
}
