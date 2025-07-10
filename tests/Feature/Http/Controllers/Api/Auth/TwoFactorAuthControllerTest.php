<?php

namespace Tests\Feature\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\ControllerTestCase;

class TwoFactorAuthControllerTest extends ControllerTestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $userWith2FA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->userWith2FA = User::factory()->create([
            'two_factor_secret'         => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode([
                'recovery-code-1',
                'recovery-code-2',
                'recovery-code-3',
                'recovery-code-4',
                'recovery-code-5',
                'recovery-code-6',
                'recovery-code-7',
                'recovery-code-8',
            ])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    #[Test]
    public function test_enable_two_factor_authentication(): void
    {
        Sanctum::actingAs($this->user);

        $mockEnable = \Mockery::mock(EnableTwoFactorAuthentication::class);
        $mockEnable->shouldReceive('__invoke')
            ->once()
            ->with($this->user)
            ->andReturnUsing(function ($user) {
                $recoveryCodes = collect()->times(8, fn () => RecoveryCode::generate())->toArray();
                $user->forceFill([
                    'two_factor_secret'         => encrypt('new-secret'),
                    'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
                ])->save();
            });

        $this->app->instance(EnableTwoFactorAuthentication::class, $mockEnable);

        $response = $this->postJson('/api/auth/2fa/enable');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'secret',
                'qr_code',
                'recovery_codes',
            ])
            ->assertJson([
                'message' => 'Two-factor authentication enabled successfully.',
                'secret'  => 'new-secret',
            ]);

        $this->assertIsArray($response->json('recovery_codes'));
        $this->assertCount(8, $response->json('recovery_codes'));
    }

    #[Test]
    public function test_enable_two_factor_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/2fa/enable');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_confirm_two_factor_with_valid_code(): void
    {
        $this->user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
        ])->save();

        Sanctum::actingAs($this->user);

        $mockProvider = \Mockery::mock(TwoFactorAuthenticationProvider::class);
        $mockProvider->shouldReceive('verify')
            ->once()
            ->with('test-secret', '123456')
            ->andReturn(true);

        $this->app->instance(TwoFactorAuthenticationProvider::class, $mockProvider);

        $response = $this->postJson('/api/auth/2fa/confirm', [
            'code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Two-factor authentication confirmed successfully.',
            ]);

        $this->assertNotNull($this->user->fresh()->two_factor_confirmed_at);
    }

    #[Test]
    public function test_confirm_two_factor_with_invalid_code(): void
    {
        $this->user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
        ])->save();

        Sanctum::actingAs($this->user);

        $mockProvider = \Mockery::mock(TwoFactorAuthenticationProvider::class);
        $mockProvider->shouldReceive('verify')
            ->once()
            ->with('test-secret', 'wrong-code')
            ->andReturn(false);

        $this->app->instance(TwoFactorAuthenticationProvider::class, $mockProvider);

        $response = $this->postJson('/api/auth/2fa/confirm', [
            'code' => 'wrong-code',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The provided two factor authentication code was invalid.',
            ]);
    }

    #[Test]
    public function test_confirm_two_factor_requires_secret(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/auth/2fa/confirm', [
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The provided two factor authentication code was invalid.',
            ]);
    }

    #[Test]
    public function test_confirm_two_factor_validates_code(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/auth/2fa/confirm', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    #[Test]
    public function test_disable_two_factor_with_valid_password(): void
    {
        Sanctum::actingAs($this->userWith2FA);

        $mockDisable = \Mockery::mock(DisableTwoFactorAuthentication::class);
        $mockDisable->shouldReceive('__invoke')
            ->once()
            ->with($this->userWith2FA)
            ->andReturnUsing(function ($user) {
                $user->forceFill([
                    'two_factor_secret'         => null,
                    'two_factor_recovery_codes' => null,
                    'two_factor_confirmed_at'   => null,
                ])->save();
            });

        $this->app->instance(DisableTwoFactorAuthentication::class, $mockDisable);

        $response = $this->postJson('/api/auth/2fa/disable', [
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Two-factor authentication disabled successfully.',
            ]);
    }

    #[Test]
    public function test_disable_two_factor_with_invalid_password(): void
    {
        Sanctum::actingAs($this->userWith2FA);

        $response = $this->postJson('/api/auth/2fa/disable', [
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function test_disable_two_factor_requires_password(): void
    {
        Sanctum::actingAs($this->userWith2FA);

        $response = $this->postJson('/api/auth/2fa/disable', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function test_verify_two_factor_with_valid_code(): void
    {
        Sanctum::actingAs($this->userWith2FA);

        $mockProvider = \Mockery::mock(TwoFactorAuthenticationProvider::class);
        $mockProvider->shouldReceive('verify')
            ->once()
            ->with('test-secret', '123456')
            ->andReturn(true);

        $this->app->instance(TwoFactorAuthenticationProvider::class, $mockProvider);

        $response = $this->postJson('/api/auth/2fa/verify', [
            'code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
            ])
            ->assertJson([
                'message' => 'Two-factor authentication verified successfully.',
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    #[Test]
    public function test_verify_two_factor_with_invalid_code(): void
    {
        Sanctum::actingAs($this->userWith2FA);

        $mockProvider = \Mockery::mock(TwoFactorAuthenticationProvider::class);
        $mockProvider->shouldReceive('verify')
            ->once()
            ->with('test-secret', 'wrong-code')
            ->andReturn(false);

        $this->app->instance(TwoFactorAuthenticationProvider::class, $mockProvider);

        $response = $this->postJson('/api/auth/2fa/verify', [
            'code' => 'wrong-code',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The provided two factor authentication code was invalid.',
            ]);
    }

    #[Test]
    public function test_verify_two_factor_with_valid_recovery_code(): void
    {
        Sanctum::actingAs($this->userWith2FA);

        $response = $this->postJson('/api/auth/2fa/verify', [
            'recovery_code' => 'recovery-code-1',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Two-factor authentication verified successfully.',
            ]);

        // Verify recovery code was replaced
        $updatedCodes = json_decode(decrypt($this->userWith2FA->fresh()->two_factor_recovery_codes), true);
        $this->assertNotContains('recovery-code-1', $updatedCodes);
        $this->assertCount(8, $updatedCodes); // Still has 8 codes
    }

    #[Test]
    public function test_verify_two_factor_with_invalid_recovery_code(): void
    {
        Sanctum::actingAs($this->userWith2FA);

        $response = $this->postJson('/api/auth/2fa/verify', [
            'recovery_code' => 'invalid-recovery-code',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The provided recovery code was invalid.',
            ]);
    }

    #[Test]
    public function test_verify_two_factor_requires_code_or_recovery_code(): void
    {
        Sanctum::actingAs($this->userWith2FA);

        $response = $this->postJson('/api/auth/2fa/verify', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'recovery_code']);
    }

    #[Test]
    public function test_verify_two_factor_prefers_recovery_code_when_both_provided(): void
    {
        Sanctum::actingAs($this->userWith2FA);

        // When both are provided, it should use recovery_code first
        $response = $this->postJson('/api/auth/2fa/verify', [
            'code'          => '123456',
            'recovery_code' => 'invalid-recovery-code',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The provided recovery code was invalid.',
            ]);
    }

    #[Test]
    public function test_regenerate_recovery_codes(): void
    {
        Sanctum::actingAs($this->userWith2FA);

        $oldCodes = json_decode(decrypt($this->userWith2FA->two_factor_recovery_codes), true);

        $response = $this->postJson('/api/auth/2fa/recovery-codes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'recovery_codes',
            ])
            ->assertJson([
                'message' => 'Recovery codes regenerated successfully.',
            ]);

        $newCodes = $response->json('recovery_codes');
        $this->assertIsArray($newCodes);
        $this->assertCount(8, $newCodes);

        // Verify codes are different
        $this->assertNotEquals($oldCodes, $newCodes);

        // Verify database was updated
        $savedCodes = json_decode(decrypt($this->userWith2FA->fresh()->two_factor_recovery_codes), true);
        $this->assertEquals($newCodes, $savedCodes);
    }

    #[Test]
    public function test_regenerate_recovery_codes_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/2fa/recovery-codes');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_enable_generates_qr_code(): void
    {
        Sanctum::actingAs($this->user);

        $mockEnable = \Mockery::mock(EnableTwoFactorAuthentication::class);
        $mockEnable->shouldReceive('__invoke')
            ->once()
            ->andReturnUsing(function ($user) {
                $recoveryCodes = collect()->times(8, fn () => RecoveryCode::generate())->toArray();
                $user->forceFill([
                    'two_factor_secret'         => encrypt('new-secret'),
                    'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
                ])->save();
            });

        $this->app->instance(EnableTwoFactorAuthentication::class, $mockEnable);

        $response = $this->postJson('/api/auth/2fa/enable');

        $response->assertStatus(200);

        $qrCode = $response->json('qr_code');
        $this->assertNotEmpty($qrCode);
        // QR code should be SVG or data URI
        $this->assertTrue(
            str_contains($qrCode, '<svg') || str_contains($qrCode, 'data:image')
        );
    }

    #[Test]
    public function test_verify_generates_new_api_token(): void
    {
        Sanctum::actingAs($this->userWith2FA);

        $initialTokenCount = $this->userWith2FA->tokens()->count();

        $mockProvider = \Mockery::mock(TwoFactorAuthenticationProvider::class);
        $mockProvider->shouldReceive('verify')
            ->once()
            ->andReturn(true);

        $this->app->instance(TwoFactorAuthenticationProvider::class, $mockProvider);

        $response = $this->postJson('/api/auth/2fa/verify', [
            'code' => '123456',
        ]);

        $response->assertStatus(200);

        // Verify new token was created
        $this->assertEquals($initialTokenCount + 1, $this->userWith2FA->fresh()->tokens()->count());

        // Verify token in response
        $token = $response->json('token');
        $this->assertMatchesRegularExpression('/^\d+\|.+$/', $token);
    }
}
