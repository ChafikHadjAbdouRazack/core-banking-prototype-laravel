<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionRateLimitMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Clear rate limit cache
        Cache::flush();

        // Set up test routes
        Route::middleware(['auth:sanctum', 'transaction.rate_limit:deposit'])
            ->post('/test-deposit', function () {
                return response()->json(['message' => 'deposit successful']);
            });

        Route::middleware(['auth:sanctum', 'transaction.rate_limit:withdraw'])
            ->post('/test-withdraw', function () {
                return response()->json(['message' => 'withdraw successful']);
            });

        Route::middleware(['auth:sanctum', 'transaction.rate_limit:transfer'])
            ->post('/test-transfer', function () {
                return response()->json(['message' => 'transfer successful']);
            });
    }

    public function test_allows_transactions_under_hourly_limit(): void
    {
        Sanctum::actingAs($this->user);

        // Deposits allow 10 per hour
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/test-deposit', ['amount' => 1000]);
            $response->assertStatus(200)
                ->assertJson(['message' => 'deposit successful']);
        }
    }

    public function test_blocks_transactions_over_hourly_limit(): void
    {
        Sanctum::actingAs($this->user);

        // Make 10 deposits (the limit)
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/test-deposit', ['amount' => 1000])->assertStatus(200);
        }

        // 11th deposit should be blocked
        $response = $this->postJson('/test-deposit', ['amount' => 1000]);
        $response->assertStatus(429)
            ->assertJsonStructure([
                'error',
                'message',
                'retry_after',
                'limit_type',
            ])
            ->assertJsonPath('limit_type', 'hourly_count');
    }

    public function test_enforces_daily_limit(): void
    {
        Sanctum::actingAs($this->user);

        // Withdrawals allow 5 per hour, 20 per day
        // We'll simulate reaching the daily limit by manipulating the cache
        $userUuid = $this->user->uuid;
        $dailyKey = "transaction_limit:withdraw:daily:{$userUuid}";

        // Set daily count to 19 (one below limit)
        Cache::put($dailyKey, 19, 86400);

        // First withdrawal should succeed (reaching daily limit)
        $response = $this->postJson('/test-withdraw', ['amount' => 1000]);
        $response->assertStatus(200);

        // Next withdrawal should fail due to daily limit
        $response = $this->postJson('/test-withdraw', ['amount' => 1000]);
        $response->assertStatus(429)
            ->assertJsonPath('limit_type', 'daily_count');
    }

    public function test_enforces_amount_limit(): void
    {
        Sanctum::actingAs($this->user);

        // Deposits have $1000 per hour limit (100000 cents)
        // Make a large deposit that exceeds the hourly amount limit
        $response = $this->postJson('/test-deposit', ['amount' => 150000]); // $1500

        $response->assertStatus(429)
            ->assertJsonStructure([
                'error',
                'message',
                'retry_after',
                'limit_type',
                'limit_details',
            ])
            ->assertJsonPath('limit_type', 'hourly_amount')
            ->assertJsonPath('limit_details.limit', 100000);
    }

    public function test_tracks_cumulative_amounts(): void
    {
        Sanctum::actingAs($this->user);

        // Make multiple small deposits that together exceed the amount limit
        // Amount limit is $1000 (100000 cents)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/test-deposit', ['amount' => 15000]); // $150 each
            $response->assertStatus(200);
        }

        // Next deposit should fail as we've used $750 of $1000 limit
        $response = $this->postJson('/test-deposit', ['amount' => 30000]); // $300
        $response->assertStatus(429)
            ->assertJsonPath('limit_type', 'hourly_amount');
    }

    public function test_different_transaction_types_have_separate_limits(): void
    {
        Sanctum::actingAs($this->user);

        // Use up deposit limit (10 per hour)
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/test-deposit', ['amount' => 1000])->assertStatus(200);
        }
        $this->postJson('/test-deposit', ['amount' => 1000])->assertStatus(429);

        // Withdrawals should still work (separate limit of 5 per hour)
        $response = $this->postJson('/test-withdraw', ['amount' => 1000]);
        $response->assertStatus(200)
            ->assertJson(['message' => 'withdraw successful']);
    }

    public function test_includes_rate_limit_headers(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/test-transfer', ['amount' => 1000]);

        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Transaction-Limit', '15')
            ->assertHeader('X-RateLimit-Transaction-Remaining')
            ->assertHeader('X-RateLimit-Transaction-Reset');
    }

    public function test_progressive_delay_increases_retry_time(): void
    {
        Sanctum::actingAs($this->user);

        // Withdrawals have progressive delay enabled
        // Use up the limit (5 per hour)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/test-withdraw', ['amount' => 1000])->assertStatus(200);
        }

        // First over-limit attempt
        $response1 = $this->postJson('/test-withdraw', ['amount' => 1000]);
        $response1->assertStatus(429);
        $retryAfter1 = $response1->json('retry_after');

        // Wait a moment and try again
        $this->travel(1)->seconds();

        // Second over-limit attempt should have longer delay
        $response2 = $this->postJson('/test-withdraw', ['amount' => 1000]);
        $response2->assertStatus(429);
        $retryAfter2 = $response2->json('retry_after');

        // Progressive delay should increase the retry time
        $this->assertGreaterThan($retryAfter1, $retryAfter2);
    }

    public function test_requires_authentication(): void
    {
        // Not authenticated
        $response = $this->postJson('/test-deposit', ['amount' => 1000]);
        $response->assertStatus(401);
    }

    public function test_handles_missing_amount_parameter(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/test-deposit', []);

        // Should still count against rate limit even without amount
        $response->assertStatus(200);

        // Check that it counted
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->user->currentAccessToken()->plainTextToken,
        ])->postJson('/test-deposit', []);

        $response->assertHeader('X-RateLimit-Transaction-Remaining', '8'); // Started with 10, used 2
    }

    public function test_rate_limits_reset_after_window(): void
    {
        Sanctum::actingAs($this->user);

        // Use up withdrawal limit
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/test-withdraw', ['amount' => 1000])->assertStatus(200);
        }
        $this->postJson('/test-withdraw', ['amount' => 1000])->assertStatus(429);

        // Travel forward past the hourly window
        $this->travel(61)->minutes();

        // Should be able to withdraw again
        $response = $this->postJson('/test-withdraw', ['amount' => 1000]);
        $response->assertStatus(200)
            ->assertJson(['message' => 'withdraw successful']);
    }

    public function test_suspicious_activity_triggers_alert(): void
    {
        Sanctum::actingAs($this->user);

        // Rapidly attempt many transactions to trigger suspicious activity
        for ($i = 0; $i < 15; $i++) {
            $this->postJson('/test-transfer', ['amount' => 1000]);
        }

        // After hitting the limit multiple times, should see security notice
        $response = $this->postJson('/test-transfer', ['amount' => 1000]);
        $response->assertStatus(429)
            ->assertJsonHasPath('security_notice');
    }
}
