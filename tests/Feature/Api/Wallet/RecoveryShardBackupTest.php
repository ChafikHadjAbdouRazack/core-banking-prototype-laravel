<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Wallet;

use App\Domain\KeyManagement\Models\RecoveryShardCloudBackup;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RecoveryShardBackupTest extends TestCase
{
    protected User $user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token', ['read', 'write'])->plainTextToken;
    }

    // --- Store ---

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/wallet/recovery-shard-backup', [
            'device_id'            => 'device_001',
            'backup_provider'      => 'icloud',
            'encrypted_shard_hash' => 'abc123hash',
            'shard_version'        => 'v1',
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/wallet/recovery-shard-backup', []);

        $response->assertUnprocessable();
    }

    public function test_store_validates_backup_provider(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/wallet/recovery-shard-backup', [
                'device_id'            => 'device_001',
                'backup_provider'      => 'dropbox',
                'encrypted_shard_hash' => 'abc123hash',
                'shard_version'        => 'v1',
            ]);

        $response->assertUnprocessable();
    }

    public function test_store_creates_backup(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/wallet/recovery-shard-backup', [
                'device_id'            => 'device_001',
                'backup_provider'      => 'icloud',
                'encrypted_shard_hash' => 'sha256_abc123',
                'shard_version'        => 'v1',
                'metadata'             => ['cloud_path' => '/backups/shard_001'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.device_id', 'device_001')
            ->assertJsonPath('data.backup_provider', 'icloud')
            ->assertJsonPath('data.encrypted_shard_hash', 'sha256_abc123')
            ->assertJsonPath('data.shard_version', 'v1')
            ->assertJsonPath('data.metadata.cloud_path', '/backups/shard_001');

        $this->assertDatabaseHas('recovery_shard_cloud_backups', [
            'user_id'         => $this->user->id,
            'device_id'       => 'device_001',
            'backup_provider' => 'icloud',
        ]);
    }

    public function test_store_upserts_on_duplicate(): void
    {
        // Create initial backup
        $this->withToken($this->token)
            ->postJson('/api/v1/wallet/recovery-shard-backup', [
                'device_id'            => 'device_001',
                'backup_provider'      => 'icloud',
                'encrypted_shard_hash' => 'hash_v1',
                'shard_version'        => 'v1',
            ]);

        // Upsert with new hash
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/wallet/recovery-shard-backup', [
                'device_id'            => 'device_001',
                'backup_provider'      => 'icloud',
                'encrypted_shard_hash' => 'hash_v2',
                'shard_version'        => 'v2',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.encrypted_shard_hash', 'hash_v2')
            ->assertJsonPath('data.shard_version', 'v2');

        // Only one record exists
        $this->assertDatabaseCount('recovery_shard_cloud_backups', 1);
    }

    // --- Show ---

    public function test_show_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/wallet/recovery-shard-backup');

        $response->assertUnauthorized();
    }

    public function test_show_returns_user_backups(): void
    {
        RecoveryShardCloudBackup::create([
            'user_id'              => $this->user->id,
            'device_id'            => 'device_001',
            'backup_provider'      => 'icloud',
            'encrypted_shard_hash' => 'hash_1',
            'shard_version'        => 'v1',
        ]);
        RecoveryShardCloudBackup::create([
            'user_id'              => $this->user->id,
            'device_id'            => 'device_002',
            'backup_provider'      => 'google_drive',
            'encrypted_shard_hash' => 'hash_2',
            'shard_version'        => 'v1',
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/wallet/recovery-shard-backup');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_show_filters_by_device_id(): void
    {
        RecoveryShardCloudBackup::create([
            'user_id'              => $this->user->id,
            'device_id'            => 'device_001',
            'backup_provider'      => 'icloud',
            'encrypted_shard_hash' => 'hash_1',
            'shard_version'        => 'v1',
        ]);
        RecoveryShardCloudBackup::create([
            'user_id'              => $this->user->id,
            'device_id'            => 'device_002',
            'backup_provider'      => 'google_drive',
            'encrypted_shard_hash' => 'hash_2',
            'shard_version'        => 'v1',
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/wallet/recovery-shard-backup?device_id=device_001');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.device_id', 'device_001');
    }

    public function test_show_does_not_return_other_users_backups(): void
    {
        $otherUser = User::factory()->create();

        RecoveryShardCloudBackup::create([
            'user_id'              => $otherUser->id,
            'device_id'            => 'device_other',
            'backup_provider'      => 'icloud',
            'encrypted_shard_hash' => 'other_hash',
            'shard_version'        => 'v1',
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/wallet/recovery-shard-backup');

        $response->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_show_returns_empty_for_new_user(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/wallet/recovery-shard-backup');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);
    }

    // --- Destroy ---

    public function test_destroy_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/v1/wallet/recovery-shard-backup', [
            'device_id'       => 'device_001',
            'backup_provider' => 'icloud',
        ]);

        $response->assertUnauthorized();
    }

    public function test_destroy_deletes_matching_backup(): void
    {
        RecoveryShardCloudBackup::create([
            'user_id'              => $this->user->id,
            'device_id'            => 'device_001',
            'backup_provider'      => 'icloud',
            'encrypted_shard_hash' => 'hash_1',
            'shard_version'        => 'v1',
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson('/api/v1/wallet/recovery-shard-backup', [
                'device_id'       => 'device_001',
                'backup_provider' => 'icloud',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('recovery_shard_cloud_backups', [
            'user_id'         => $this->user->id,
            'device_id'       => 'device_001',
            'backup_provider' => 'icloud',
        ]);
    }

    public function test_destroy_returns_404_for_nonexistent(): void
    {
        $response = $this->withToken($this->token)
            ->deleteJson('/api/v1/wallet/recovery-shard-backup', [
                'device_id'       => 'nonexistent',
                'backup_provider' => 'icloud',
            ]);

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'BACKUP_NOT_FOUND');
    }

    public function test_destroy_does_not_delete_other_users_backups(): void
    {
        $otherUser = User::factory()->create();

        RecoveryShardCloudBackup::create([
            'user_id'              => $otherUser->id,
            'device_id'            => 'device_001',
            'backup_provider'      => 'icloud',
            'encrypted_shard_hash' => 'hash_1',
            'shard_version'        => 'v1',
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson('/api/v1/wallet/recovery-shard-backup', [
                'device_id'       => 'device_001',
                'backup_provider' => 'icloud',
            ]);

        $response->assertNotFound();

        // Other user's backup still exists
        $this->assertDatabaseHas('recovery_shard_cloud_backups', [
            'user_id'   => $otherUser->id,
            'device_id' => 'device_001',
        ]);
    }

    // --- Routes ---

    public function test_routes_exist(): void
    {
        $routes = app('router')->getRoutes();
        $this->assertNotNull($routes->getByName('mobile.wallet.recovery-shard-backup.store'));
        $this->assertNotNull($routes->getByName('mobile.wallet.recovery-shard-backup.show'));
        $this->assertNotNull($routes->getByName('mobile.wallet.recovery-shard-backup.destroy'));
    }
}
