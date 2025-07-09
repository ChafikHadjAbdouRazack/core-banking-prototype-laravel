<?php

namespace Tests\Feature\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => Hash::make('oldpassword'),
        ]);
    }

    public function test_forgot_password_sends_reset_link(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'test@example.com'])
            ->andReturn(Password::RESET_LINK_SENT);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => __('passwords.sent'),
            ]);
    }

    public function test_forgot_password_fails_for_invalid_email(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'invalid@example.com'])
            ->andReturn(Password::INVALID_USER);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'invalid@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => [__('passwords.user')],
                ],
            ]);
    }

    public function test_forgot_password_validates_email_format(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_requires_email(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_reset_password_with_valid_token(): void
    {
        Event::fake();

        Password::shouldReceive('reset')
            ->once()
            ->withArgs(function ($credentials, $callback) {
                // Check credentials
                $this->assertEquals([
                    'email'                 => 'test@example.com',
                    'password'              => 'newpassword123',
                    'password_confirmation' => 'newpassword123',
                    'token'                 => 'valid-token',
                ], $credentials);

                // Simulate the callback
                $callback($this->user, 'newpassword123');

                return true;
            })
            ->andReturn(Password::PASSWORD_RESET);

        $response = $this->postJson('/api/auth/reset-password', [
            'email'                 => 'test@example.com',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token'                 => 'valid-token',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => __('passwords.reset'),
            ]);

        // Verify password was hashed and saved
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));

        // Verify remember token was regenerated
        $this->assertNotNull($this->user->remember_token);
        $this->assertEquals(60, strlen($this->user->remember_token));

        // Verify PasswordReset event was dispatched
        Event::assertDispatched(PasswordReset::class, function ($event) {
            return $event->user->id === $this->user->id;
        });
    }

    public function test_reset_password_with_invalid_token(): void
    {
        Password::shouldReceive('reset')
            ->once()
            ->andReturn(Password::INVALID_TOKEN);

        $response = $this->postJson('/api/auth/reset-password', [
            'email'                 => 'test@example.com',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token'                 => 'invalid-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => [__('passwords.token')],
                ],
            ]);
    }

    public function test_reset_password_with_expired_token(): void
    {
        Password::shouldReceive('reset')
            ->once()
            ->andReturn(Password::INVALID_TOKEN);

        $response = $this->postJson('/api/auth/reset-password', [
            'email'                 => 'test@example.com',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token'                 => 'expired-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => [__('passwords.token')],
                ],
            ]);
    }

    public function test_reset_password_validation_errors(): void
    {
        // Missing all fields
        $response = $this->postJson('/api/auth/reset-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'email', 'password']);

        // Invalid email format
        $response = $this->postJson('/api/auth/reset-password', [
            'email'                 => 'not-an-email',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token'                 => 'some-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Password too short
        $response = $this->postJson('/api/auth/reset-password', [
            'email'                 => 'test@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
            'token'                 => 'some-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Password confirmation mismatch
        $response = $this->postJson('/api/auth/reset-password', [
            'email'                 => 'test@example.com',
            'password'              => 'newpassword123',
            'password_confirmation' => 'differentpassword',
            'token'                 => 'some-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_reset_password_for_invalid_user(): void
    {
        Password::shouldReceive('reset')
            ->once()
            ->andReturn(Password::INVALID_USER);

        $response = $this->postJson('/api/auth/reset-password', [
            'email'                 => 'nonexistent@example.com',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token'                 => 'some-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => [__('passwords.user')],
                ],
            ]);
    }

    public function test_forgot_password_throttling(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::RESET_THROTTLED);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => [__('passwords.throttled')],
                ],
            ]);
    }

    public function test_reset_password_changes_user_password(): void
    {
        Event::fake();

        $oldPasswordHash = $this->user->password;

        Password::shouldReceive('reset')
            ->once()
            ->withArgs(function ($credentials, $callback) {
                $callback($this->user, 'newpassword123');

                return true;
            })
            ->andReturn(Password::PASSWORD_RESET);

        $response = $this->postJson('/api/auth/reset-password', [
            'email'                 => 'test@example.com',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token'                 => 'valid-token',
        ]);

        $response->assertStatus(200);

        // Verify password was changed
        $this->assertNotEquals($oldPasswordHash, $this->user->fresh()->password);
        $this->assertTrue(Hash::check('newpassword123', $this->user->fresh()->password));
        $this->assertFalse(Hash::check('oldpassword', $this->user->fresh()->password));
    }

    public function test_forgot_password_returns_success_even_for_nonexistent_email(): void
    {
        // This is a security feature - we don't want to reveal if an email exists
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'doesnotexist@example.com'])
            ->andReturn(Password::INVALID_USER);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'doesnotexist@example.com',
        ]);

        // Should return 422 with validation error, not success
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_reset_password_generates_new_remember_token(): void
    {
        Event::fake();

        $oldRememberToken = $this->user->remember_token;

        Password::shouldReceive('reset')
            ->once()
            ->withArgs(function ($credentials, $callback) {
                $callback($this->user, 'newpassword123');

                return true;
            })
            ->andReturn(Password::PASSWORD_RESET);

        $response = $this->postJson('/api/auth/reset-password', [
            'email'                 => 'test@example.com',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token'                 => 'valid-token',
        ]);

        $response->assertStatus(200);

        $newRememberToken = $this->user->fresh()->remember_token;
        $this->assertNotEquals($oldRememberToken, $newRememberToken);
        $this->assertEquals(60, strlen($newRememberToken));
    }
}
