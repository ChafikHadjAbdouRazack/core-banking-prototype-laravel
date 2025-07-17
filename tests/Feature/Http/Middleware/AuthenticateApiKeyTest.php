<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticateApiKeyTest extends TestCase
{
    use RefreshDatabase;

    protected ApiKey $apiKey;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create test API key
        $this->apiKey = ApiKey::create([
            'user_uuid' => $this->user->uuid,
            'name' => 'Test API Key',
            'key' => 'test_' . bin2hex(random_bytes(16)),
            'secret' => hash('sha256', 'test_secret'),
            'permissions' => ['read', 'write'],
            'allowed_ips' => null, // Allow all IPs
            'expires_at' => now()->addYear(),
            'is_active' => true,
        ]);

        // Set up test routes
        Route::middleware(['auth.api_key'])->get('/test-api', function () {
            return response()->json(['message' => 'success']);
        });

        Route::middleware(['auth.api_key:write'])->post('/test-api-write', function () {
            return response()->json(['message' => 'success']);
        });

        Route::middleware(['auth.api_key:admin'])->get('/test-api-admin', function () {
            return response()->json(['message' => 'success']);
        });
    }

    #[Test]
    public function test_allows_valid_api_key(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
        ])->getJson('/test-api');

        $response->assertStatus(200)
            ->assertJson(['message' => 'success']);
    }

    #[Test]
    public function test_rejects_missing_api_key(): void
    {
        $response = $this->getJson('/test-api');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'API key is required',
            ]);
    }

    #[Test]
    public function test_rejects_invalid_api_key(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_key_12345',
        ])->getJson('/test-api');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid API key',
            ]);
    }

    #[Test]
    public function test_rejects_expired_api_key(): void
    {
        $expiredKey = ApiKey::create([
            'user_uuid' => $this->user->uuid,
            'name' => 'Expired Key',
            'key' => 'expired_' . bin2hex(random_bytes(16)),
            'secret' => hash('sha256', 'expired_secret'),
            'permissions' => ['read'],
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $expiredKey->key,
        ])->getJson('/test-api');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'API key has expired',
            ]);
    }

    #[Test]
    public function test_rejects_inactive_api_key(): void
    {
        $inactiveKey = ApiKey::create([
            'user_uuid' => $this->user->uuid,
            'name' => 'Inactive Key',
            'key' => 'inactive_' . bin2hex(random_bytes(16)),
            'secret' => hash('sha256', 'inactive_secret'),
            'permissions' => ['read'],
            'expires_at' => now()->addYear(),
            'is_active' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $inactiveKey->key,
        ])->getJson('/test-api');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid API key',
            ]);
    }

    #[Test]
    public function test_enforces_ip_restrictions(): void
    {
        $restrictedKey = ApiKey::create([
            'user_uuid' => $this->user->uuid,
            'name' => 'IP Restricted Key',
            'key' => 'restricted_' . bin2hex(random_bytes(16)),
            'secret' => hash('sha256', 'restricted_secret'),
            'permissions' => ['read'],
            'allowed_ips' => ['192.168.1.1', '10.0.0.1'],
            'expires_at' => now()->addYear(),
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $restrictedKey->key,
            'X-Forwarded-For' => '192.168.2.1', // Different IP
        ])->getJson('/test-api');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Forbidden',
                'message' => 'Access denied from this IP address',
            ]);
    }

    #[Test]
    public function test_allows_whitelisted_ip(): void
    {
        $restrictedKey = ApiKey::create([
            'user_uuid' => $this->user->uuid,
            'name' => 'IP Restricted Key',
            'key' => 'restricted_' . bin2hex(random_bytes(16)),
            'secret' => hash('sha256', 'restricted_secret'),
            'permissions' => ['read'],
            'allowed_ips' => ['192.168.1.1', '127.0.0.1'],
            'expires_at' => now()->addYear(),
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $restrictedKey->key,
        ])->getJson('/test-api');

        $response->assertStatus(200)
            ->assertJson(['message' => 'success']);
    }

    #[Test]
    public function test_enforces_permission_requirements(): void
    {
        // API key only has 'read' permission, trying to access 'write' endpoint
        $readOnlyKey = ApiKey::create([
            'user_uuid' => $this->user->uuid,
            'name' => 'Read Only Key',
            'key' => 'readonly_' . bin2hex(random_bytes(16)),
            'secret' => hash('sha256', 'readonly_secret'),
            'permissions' => ['read'],
            'expires_at' => now()->addYear(),
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $readOnlyKey->key,
        ])->postJson('/test-api-write');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Forbidden',
                'message' => 'Insufficient permissions',
            ]);
    }

    #[Test]
    public function test_allows_sufficient_permissions(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
        ])->postJson('/test-api-write');

        $response->assertStatus(200)
            ->assertJson(['message' => 'success']);
    }

    #[Test]
    public function test_logs_api_key_usage(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
        ])->getJson('/test-api');

        $this->assertDatabaseHas('api_key_logs', [
            'api_key_id' => $this->apiKey->id,
            'endpoint' => '/test-api',
            'method' => 'GET',
            'status_code' => 200,
        ]);
    }

    #[Test]
    public function test_logs_failed_attempts(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer invalid_key',
        ])->getJson('/test-api');

        $this->assertDatabaseHas('api_key_logs', [
            'api_key_id' => null,
            'endpoint' => '/test-api',
            'method' => 'GET',
            'status_code' => 401,
        ]);
    }

    #[Test]
    public function test_includes_user_in_request(): void
    {
        Route::middleware(['auth.api_key'])->get('/test-user', function () {
            $user = request()->user();

            return response()->json([
                'user_id' => $user->id ?? null,
                'user_uuid' => $user->uuid ?? null,
            ]);
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey->key,
        ])->getJson('/test-user');

        $response->assertStatus(200)
            ->assertJson([
                'user_id' => $this->user->id,
                'user_uuid' => $this->user->uuid,
            ]);
    }

    #[Test]
    public function test_rejects_malformed_authorization_header(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'InvalidFormat ' . $this->apiKey->key,
        ])->getJson('/test-api');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'API key is required',
            ]);
    }
}
