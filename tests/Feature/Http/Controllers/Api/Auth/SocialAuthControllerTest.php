<?php

namespace Tests\Feature\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\ControllerTestCase;

class SocialAuthControllerTest extends ControllerTestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_redirect_returns_oauth_url_for_valid_provider(): void
    {
        $expectedUrl = 'https://accounts.google.com/o/oauth2/auth?client_id=test&redirect_uri=test&scope=test&response_type=code';

        $provider = \Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturnSelf();
        $provider->shouldReceive('getTargetUrl')->once()->andReturn($expectedUrl);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->getJson('/api/auth/social/google');

        $response->assertStatus(200)
            ->assertJson([
                'url' => $expectedUrl,
            ]);
    }

    #[Test]
    public function test_redirect_supports_multiple_providers(): void
    {
        $providers = ['google', 'facebook', 'github'];

        foreach ($providers as $provider) {
            $expectedUrl = "https://example.com/oauth/{$provider}";

            $providerMock = \Mockery::mock('Laravel\\Socialite\\Two\\' . ucfirst($provider) . 'Provider');
            $providerMock->shouldReceive('stateless')->once()->andReturnSelf();
            $providerMock->shouldReceive('redirect')->once()->andReturnSelf();
            $providerMock->shouldReceive('getTargetUrl')->once()->andReturn($expectedUrl);

            Socialite::shouldReceive('driver')
                ->once()
                ->with($provider)
                ->andReturn($providerMock);

            $response = $this->getJson("/api/auth/social/{$provider}");

            $response->assertStatus(200)
                ->assertJson([
                    'url' => $expectedUrl,
                ]);
        }
    }

    #[Test]
    public function test_redirect_fails_for_invalid_provider(): void
    {
        $response = $this->getJson('/api/auth/social/invalid-provider');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid provider',
            ]);
    }

    #[Test]
    public function test_redirect_handles_unconfigured_provider(): void
    {
        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andThrow(new \Exception('Provider not configured'));

        $response = $this->getJson('/api/auth/social/google');

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Provider not configured',
            ]);
    }

    #[Test]
    public function test_callback_creates_new_user_from_oauth(): void
    {
        $socialiteUser = \Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-123');
        $socialiteUser->shouldReceive('getName')->andReturn('John Doe');
        $socialiteUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        $provider = \Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->postJson('/api/auth/social/google/callback', [
            'code' => 'valid-oauth-code',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'oauth_provider',
                    'oauth_id',
                    'avatar',
                    'email_verified_at',
                ],
                'token',
                'message',
            ])
            ->assertJson([
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'oauth_provider' => 'google',
                    'oauth_id' => 'google-123',
                    'avatar' => 'https://example.com/avatar.jpg',
                ],
                'message' => 'Authenticated successfully',
            ]);

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'oauth_provider' => 'google',
            'oauth_id' => 'google-123',
        ]);

        // Verify email is auto-verified
        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }

    #[Test]
    public function test_callback_authenticates_existing_user(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'oauth_provider' => 'google',
            'oauth_id' => 'google-123',
        ]);

        $socialiteUser = \Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-123');
        $socialiteUser->shouldReceive('getName')->andReturn('Existing User');
        $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        $provider = \Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->postJson('/api/auth/social/google/callback', [
            'code' => 'valid-oauth-code',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $existingUser->id,
                    'email' => 'existing@example.com',
                ],
                'message' => 'Authenticated successfully',
            ]);

        // Verify no duplicate user was created
        $this->assertEquals(1, User::where('email', 'existing@example.com')->count());
    }

    #[Test]
    public function test_callback_links_oauth_to_existing_email_user(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'oauth_provider' => null,
            'oauth_id' => null,
        ]);

        $socialiteUser = \Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-456');
        $socialiteUser->shouldReceive('getName')->andReturn('Existing User');
        $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        $provider = \Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->postJson('/api/auth/social/google/callback', [
            'code' => 'valid-oauth-code',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $existingUser->id,
                    'email' => 'existing@example.com',
                    'oauth_provider' => 'google',
                    'oauth_id' => 'google-456',
                ],
            ]);

        // Verify OAuth info was updated
        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'oauth_provider' => 'google',
            'oauth_id' => 'google-456',
        ]);
    }

    #[Test]
    public function test_callback_requires_code_parameter(): void
    {
        $response = $this->postJson('/api/auth/social/google/callback', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    #[Test]
    public function test_callback_fails_for_invalid_provider(): void
    {
        $response = $this->postJson('/api/auth/social/invalid-provider/callback', [
            'code' => 'some-code',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid provider',
            ]);
    }

    #[Test]
    public function test_callback_handles_oauth_failure(): void
    {
        $provider = \Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andThrow(new \Exception('OAuth failed'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->postJson('/api/auth/social/google/callback', [
            'code' => 'invalid-code',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Authentication failed',
            ]);
    }

    #[Test]
    public function test_callback_includes_error_details_in_debug_mode(): void
    {
        config(['app.debug' => true]);

        $provider = \Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andThrow(new \Exception('Detailed error message'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->postJson('/api/auth/social/google/callback', [
            'code' => 'invalid-code',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Authentication failed',
                'error' => 'Detailed error message',
            ]);
    }

    #[Test]
    public function test_callback_hides_error_details_in_production(): void
    {
        config(['app.debug' => false]);

        $provider = \Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andThrow(new \Exception('Sensitive error'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->postJson('/api/auth/social/google/callback', [
            'code' => 'invalid-code',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Authentication failed',
                'error' => null,
            ]);
    }

    #[Test]
    public function test_callback_generates_api_token(): void
    {
        $socialiteUser = \Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-123');
        $socialiteUser->shouldReceive('getName')->andReturn('John Doe');
        $socialiteUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        $provider = \Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->postJson('/api/auth/social/google/callback', [
            'code' => 'valid-oauth-code',
        ]);

        $response->assertStatus(200);

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        // Verify token was created in database
        $user = User::where('email', 'john@example.com')->first();
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'api-token',
        ]);
    }

    #[Test]
    public function test_new_oauth_users_have_random_password(): void
    {
        $socialiteUser = \Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-123');
        $socialiteUser->shouldReceive('getName')->andReturn('John Doe');
        $socialiteUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        $provider = \Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->postJson('/api/auth/social/google/callback', [
            'code' => 'valid-oauth-code',
        ]);

        $response->assertStatus(200);

        $user = User::where('email', 'john@example.com')->first();

        // Password should be set (not null) and be a valid hash
        $this->assertNotNull($user->password);
        $this->assertTrue(strlen($user->password) > 50); // Bcrypt hashes are typically 60 chars
    }
}
