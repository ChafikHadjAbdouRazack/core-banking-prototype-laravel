<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiRateLimitMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear rate limit cache
        Cache::flush();

        // Set up test routes with different rate limit types
        Route::middleware(['api.rate_limit:auth'])->get('/test-auth', function () {
            return response()->json(['message' => 'success']);
        });

        Route::middleware(['api.rate_limit:transaction'])->post('/test-transaction', function () {
            return response()->json(['message' => 'success']);
        });

        Route::middleware(['api.rate_limit:query'])->get('/test-query', function () {
            return response()->json(['message' => 'success']);
        });

        Route::middleware(['api.rate_limit:public'])->get('/test-public', function () {
            return response()->json(['message' => 'success']);
        });
    }

    public function test_allows_requests_under_rate_limit(): void
    {
        // Auth endpoint allows 5 requests per minute
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/test-auth');
            $response->assertStatus(200)
                ->assertJson(['message' => 'success']);
        }
    }

    public function test_blocks_requests_over_rate_limit(): void
    {
        // Auth endpoint allows 5 requests per minute
        for ($i = 0; $i < 5; $i++) {
            $this->getJson('/test-auth')->assertStatus(200);
        }

        // 6th request should be blocked
        $response = $this->getJson('/test-auth');
        $response->assertStatus(429)
            ->assertJson([
                'error' => 'Too many requests',
            ])
            ->assertJsonHasPath('retry_after');
    }

    public function test_includes_rate_limit_headers(): void
    {
        $response = $this->getJson('/test-query');

        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit', '100')
            ->assertHeader('X-RateLimit-Remaining')
            ->assertHeader('X-RateLimit-Reset');

        // Check remaining decreases
        $remaining = $response->headers->get('X-RateLimit-Remaining');
        $this->assertEquals(99, $remaining);
    }

    public function test_different_endpoints_have_separate_limits(): void
    {
        // Use up auth limit (5 requests)
        for ($i = 0; $i < 5; $i++) {
            $this->getJson('/test-auth')->assertStatus(200);
        }
        $this->getJson('/test-auth')->assertStatus(429);

        // Query endpoint should still work (100 requests allowed)
        $response = $this->getJson('/test-query');
        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Remaining', '99');
    }

    public function test_transaction_endpoint_rate_limit(): void
    {
        // Transaction endpoint allows 30 requests per minute
        for ($i = 0; $i < 30; $i++) {
            $response = $this->postJson('/test-transaction');
            $response->assertStatus(200);
        }

        // 31st request should be blocked
        $response = $this->postJson('/test-transaction');
        $response->assertStatus(429);
    }

    public function test_public_endpoint_rate_limit(): void
    {
        // Public endpoint allows 60 requests per minute
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/test-public');
            $response->assertStatus(200);
        }

        // 61st request should be blocked
        $response = $this->getJson('/test-public');
        $response->assertStatus(429);
    }

    public function test_rate_limit_resets_after_window(): void
    {
        // Use up the limit
        for ($i = 0; $i < 5; $i++) {
            $this->getJson('/test-auth')->assertStatus(200);
        }
        $this->getJson('/test-auth')->assertStatus(429);

        // Travel forward 61 seconds (past the 60-second window)
        $this->travel(61)->seconds();

        // Should be able to make requests again
        $response = $this->getJson('/test-auth');
        $response->assertStatus(200);
    }

    public function test_blocked_duration_varies_by_endpoint_type(): void
    {
        // Auth endpoint has 5-minute (300 second) block duration
        for ($i = 0; $i < 5; $i++) {
            $this->getJson('/test-auth')->assertStatus(200);
        }

        $response = $this->getJson('/test-auth');
        $response->assertStatus(429);
        $retryAfter = $response->json('retry_after');

        // Should be close to 300 seconds
        $this->assertGreaterThanOrEqual(295, $retryAfter);
        $this->assertLessThanOrEqual(300, $retryAfter);
    }

    public function test_ipv6_addresses_are_handled_correctly(): void
    {
        // Simulate request from IPv6 address
        $response = $this->withHeaders([
            'X-Forwarded-For' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
        ])->getJson('/test-public');

        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit', '60');
    }

    public function test_rate_limit_key_includes_endpoint_type(): void
    {
        // Make a request to auth endpoint
        $this->getJson('/test-auth')->assertStatus(200);

        // Check cache key exists (implementation detail test)
        $ip = request()->ip();
        $authKey = "rate_limit:auth:{$ip}";
        $this->assertTrue(Cache::has($authKey));

        // Make a request to query endpoint
        $this->getJson('/test-query')->assertStatus(200);

        // Should have separate cache key
        $queryKey = "rate_limit:query:{$ip}";
        $this->assertTrue(Cache::has($queryKey));
    }

    public function test_invalid_rate_limit_type_uses_default(): void
    {
        // Create route with invalid rate limit type
        Route::middleware(['api.rate_limit:invalid'])->get('/test-invalid', function () {
            return response()->json(['message' => 'success']);
        });

        $response = $this->getJson('/test-invalid');

        // Should fall back to 'query' type (100 requests per minute)
        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit', '100');
    }
}
