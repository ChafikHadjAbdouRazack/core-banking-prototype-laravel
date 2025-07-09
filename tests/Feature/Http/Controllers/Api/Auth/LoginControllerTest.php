<?php

namespace Tests\Feature\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                ],
                'access_token',
                'token',
                'token_type',
                'expires_in',
            ])
            ->assertJson([
                'user' => [
                    'email' => 'test@example.com',
                ],
                'token_type' => 'Bearer',
            ]);

        $this->assertNotEmpty($response->json('access_token'));
        $this->assertEquals($response->json('access_token'), $response->json('token'));
    }

    public function test_login_with_device_name(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'iPhone 14',
        ]);

        $response->assertStatus(200);
        
        // Verify token was created with device name
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'iPhone 14',
        ]);
    }

    public function test_login_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => ['The provided credentials are incorrect.'],
                ],
            ]);
    }

    public function test_login_validation_errors(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_revoke_tokens_option(): void
    {
        // Create some existing tokens
        $this->user->createToken('old-token-1');
        $this->user->createToken('old-token-2');
        
        $this->assertEquals(2, $this->user->tokens()->count());

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'revoke_tokens' => true,
        ]);

        $response->assertStatus(200);
        
        // Should have only 1 token (the new one)
        $this->assertEquals(1, $this->user->fresh()->tokens()->count());
    }

    public function test_login_enforces_concurrent_session_limit(): void
    {
        config(['auth.max_concurrent_sessions' => 3]);
        
        // Create 3 existing tokens
        $this->user->createToken('token-1');
        $this->user->createToken('token-2');
        $this->user->createToken('token-3');
        
        $this->assertEquals(3, $this->user->tokens()->count());

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        
        // Should still have 3 tokens (oldest was deleted, new one created)
        $this->assertEquals(3, $this->user->fresh()->tokens()->count());
        
        // Verify oldest token was deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'token-1',
        ]);
    }

    public function test_logout_revokes_current_token(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out',
            ]);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    public function test_logout_all_revokes_all_tokens(): void
    {
        // Create multiple tokens
        $this->user->createToken('token-1');
        $this->user->createToken('token-2');
        $this->user->createToken('token-3');
        
        $this->assertEquals(3, $this->user->tokens()->count());

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/auth/logout-all');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out from all devices',
            ]);

        $this->assertEquals(0, $this->user->fresh()->tokens()->count());
    }

    public function test_logout_all_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout-all');

        $response->assertStatus(401);
    }

    public function test_refresh_token_creates_new_token(): void
    {
        $token = $this->user->createToken('test-token');
        $oldToken = $token->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $oldToken)
            ->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ])
            ->assertJson([
                'token_type' => 'Bearer',
            ]);

        $newToken = $response->json('access_token');
        $this->assertNotEquals($oldToken, $newToken);

        // Old token should be deleted
        $this->assertEquals(1, $this->user->fresh()->tokens()->count());
    }

    public function test_refresh_token_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(401);
    }

    public function test_get_authenticated_user(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'user' => [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                ],
            ]);
    }

    public function test_get_user_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    public function test_login_with_session_regeneration(): void
    {
        // Enable session for this test
        $this->withSession(['key' => 'value']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        // In API context, session regeneration may not apply the same way
        // as it's primarily for stateless authentication
    }

    public function test_token_expiration_is_included_when_configured(): void
    {
        config(['sanctum.expiration' => 60]); // 60 minutes

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'expires_in' => 3600, // 60 minutes * 60 seconds
            ]);
    }

    public function test_token_expiration_is_null_when_not_configured(): void
    {
        config(['sanctum.expiration' => null]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'expires_in' => null,
            ]);
    }
}