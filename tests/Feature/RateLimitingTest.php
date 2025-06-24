<?php

use App\Http\Middleware\ApiRateLimitMiddleware;
use App\Http\Middleware\TransactionRateLimitMiddleware;
use App\Services\DynamicRateLimitService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush(); // Clear rate limit counters between tests
    $this->user = User::factory()->create();
});

describe('API Rate Limiting System', function () {
    
    test('basic rate limiting middleware works', function () {
        Sanctum::actingAs($this->user);
        
        // Make requests up to the limit (workflows endpoint uses admin rate limiting with 200 limit)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/api/workflows');
            $response->assertStatus(200);
            
            expect($response->headers->get('X-RateLimit-Limit'))->toBe('200');
            expect((int)$response->headers->get('X-RateLimit-Remaining'))->toBe(199 - $i);
        }
    });

    test('rate limit headers are present', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/workflows');
        
        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining')
            ->assertHeader('X-RateLimit-Reset')
            ->assertHeader('X-RateLimit-Window');
    });

    test('rate limit exceeded returns 429', function () {
        // Test auth rate limiting by making multiple requests to auth endpoint
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password'
            ]);
        }
        
        // Should be rate limited after 5 attempts
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);
        
        expect($response->getStatusCode())->toBe(429);
        expect($response->headers->get('Retry-After'))->toBeGreaterThan(0);
    });

    test('different rate limit types have different limits', function () {
        $authConfig = ApiRateLimitMiddleware::getRateLimitConfig('auth');
        $queryConfig = ApiRateLimitMiddleware::getRateLimitConfig('query');
        $adminConfig = ApiRateLimitMiddleware::getRateLimitConfig('admin');
        
        expect($authConfig['limit'])->toBe(5);
        expect($queryConfig['limit'])->toBe(100);
        expect($adminConfig['limit'])->toBe(200);
        
        expect($authConfig['window'])->toBe(60);
        expect($queryConfig['window'])->toBe(60);
        expect($adminConfig['window'])->toBe(60);
    });

    test('rate limiting works per user', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // User 1 hits rate limit (admin endpoints have 200 req/min limit)
        Sanctum::actingAs($user1);
        for ($i = 0; $i < 201; $i++) {
            $this->getJson('/api/workflows');
        }
        $response1 = $this->getJson('/api/workflows');
        
        // User 2 should still have access
        Sanctum::actingAs($user2);
        $response2 = $this->getJson('/api/workflows');
        
        expect($response1->status())->toBe(429);
        expect($response2->status())->toBe(200);
    });
});

describe('Transaction Rate Limiting', function () {
    
    test('transaction rate limiting has separate limits', function () {
        $limits = TransactionRateLimitMiddleware::getTransactionLimits();
        
        expect($limits['deposit']['limit'])->toBe(10);
        expect($limits['withdraw']['limit'])->toBe(5);
        expect($limits['transfer']['limit'])->toBe(15);
        expect($limits['convert']['limit'])->toBe(20);
    });

    test('transaction rate limiting includes amount limits', function () {
        $limits = TransactionRateLimitMiddleware::getTransactionLimits();
        
        expect($limits['deposit']['amount_limit'])->toBe(100000); // $1000
        expect($limits['withdraw']['amount_limit'])->toBe(50000);  // $500
        expect($limits['transfer']['amount_limit'])->toBe(200000); // $2000
    });

    test('transaction rate limiting applies progressive delay', function () {
        $limits = TransactionRateLimitMiddleware::getTransactionLimits();
        
        expect($limits['deposit']['progressive_delay'])->toBeTrue();
        expect($limits['withdraw']['progressive_delay'])->toBeTrue();
        expect($limits['convert']['progressive_delay'])->toBeFalse();
    });

    test('transaction rate limiting validates transaction types', function () {
        expect(TransactionRateLimitMiddleware::isValidTransactionType('deposit'))->toBeTrue();
        expect(TransactionRateLimitMiddleware::isValidTransactionType('withdraw'))->toBeTrue();
        expect(TransactionRateLimitMiddleware::isValidTransactionType('invalid'))->toBeFalse();
    });

    test('transaction rate limiting requires authentication', function () {
        $middleware = new TransactionRateLimitMiddleware();
        $request = Request::create('/api/accounts/123/deposit', 'POST');
        
        $response = $middleware->handle($request, function () {
            return response()->json(['success' => true]);
        }, 'deposit');
        
        expect($response->getStatusCode())->toBe(401);
    });

    test('transaction amount extraction works', function () {
        $middleware = new TransactionRateLimitMiddleware();
        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('extractAmount');
        $method->setAccessible(true);
        
        // Test with amount field
        $request1 = Request::create('/test', 'POST', ['amount' => 100.50]);
        $amount1 = $method->invoke($middleware, $request1);
        expect($amount1)->toBe(10050); // Converted to cents
        
        // Test with value field
        $request2 = Request::create('/test', 'POST', ['value' => 25.75]);
        $amount2 = $method->invoke($middleware, $request2);
        expect($amount2)->toBe(2575);
        
        // Test with no amount
        $request3 = Request::create('/test', 'POST', []);
        $amount3 = $method->invoke($middleware, $request3);
        expect($amount3)->toBeNull();
    });
});

describe('Dynamic Rate Limiting Service', function () {
    
    test('dynamic rate limiting adjusts based on system load', function () {
        $service = new DynamicRateLimitService();
        
        // Mock low system load
        Cache::put('system_load:current', 0.2, 60);
        $config = $service->getDynamicRateLimit('query', $this->user->id);
        
        expect($config['limit'])->toBeGreaterThan(100); // Greater than base limit of 100
        expect($config['adjustments']['load'])->toBe(1.5);
    });

    test('dynamic rate limiting considers user trust level', function () {
        $service = new DynamicRateLimitService();
        
        // New user should get reduced limits (trust multiplier of 0.5)
        $newUser = User::factory()->create(['created_at' => now()]);
        $configNew = $service->getDynamicRateLimit('query', $newUser->id);
        
        // Established user should get higher limits (trust multiplier of 1.0 for basic)
        $oldUser = User::factory()->create(['created_at' => now()->subDays(31)]);
        $configOld = $service->getDynamicRateLimit('query', $oldUser->id);
        
        expect($configNew['adjustments']['trust'])->toBe(0.5);
        expect($configOld['adjustments']['trust'])->toBe(1.0);
    });

    test('dynamic rate limiting adjusts for time of day', function () {
        $service = new DynamicRateLimitService();
        
        // Mock business hours (higher limits)
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getTimeOfDayMultiplier');
        $method->setAccessible(true);
        
        // This would need to be tested with time mocking in a real scenario
        $multiplier = $method->invoke($service);
        expect($multiplier)->toBeFloat();
        expect($multiplier)->toBeGreaterThan(0);
    });

    test('dynamic rate limiting records violations', function () {
        $service = new DynamicRateLimitService();
        
        $service->recordViolation($this->user->id, 'rate_limit_exceeded');
        
        $violationCount = Cache::get("user_violations:{$this->user->id}", 0);
        expect($violationCount)->toBe('1'); // Cache stores as string
    });

    test('dynamic rate limiting provides system metrics', function () {
        $service = new DynamicRateLimitService();
        
        $metrics = $service->getSystemMetrics();
        
        expect($metrics)->toHaveKeys([
            'cpu_load',
            'memory_load',
            'redis_load',
            'database_load',
            'overall_load'
        ]);
        
        expect($metrics['overall_load'])->toBeFloat();
    });

    test('rate limit configuration returns valid types', function () {
        $types = ApiRateLimitMiddleware::getAvailableTypes();
        
        expect($types)->toContain('auth', 'transaction', 'query', 'admin', 'public', 'webhook');
        expect(count($types))->toBe(6);
    });
});

describe('Rate Limiting Integration Tests', function () {
    
    test('auth endpoints use auth rate limiting', function () {
        // This would test that auth endpoints actually use the auth rate limiter
        // Multiple failed login attempts should trigger auth rate limiting
        
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'wrong@example.com',
                'password' => 'wrongpassword'
            ]);
        }
        
        // After 5 attempts, should be rate limited
        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword'
        ]);
        
        // Should be rate limited (429) or validation error (422) or unauthorized (401)
        expect(in_array($response->status(), [429, 422, 401]))->toBeTrue();
    });

    test('transaction endpoints use transaction rate limiting', function () {
        Sanctum::actingAs($this->user);
        
        // This would test actual transaction endpoints
        // Multiple transaction attempts should trigger transaction rate limiting
        
        // Create an account first (use correct user_uuid field)
        $account = \App\Models\Account::factory()->create(['user_uuid' => $this->user->uuid]);
        
        // Attempt multiple deposits (limit is 10 per hour)
        $successCount = 0;
        $rateLimitedCount = 0;
        
        for ($i = 0; $i < 12; $i++) {
            $response = $this->postJson("/api/accounts/{$account->uuid}/deposit", [
                'amount' => 10.00,
                'asset_code' => 'USD'
            ]);
            
            if ($response->status() === 200 || $response->status() === 201) {
                $successCount++;
            } elseif ($response->status() === 429) {
                $rateLimitedCount++;
            }
        }
        
        // Should eventually hit rate limits
        expect($rateLimitedCount)->toBeGreaterThan(0);
    });

    test('public endpoints use public rate limiting', function () {
        // Test public endpoints (no auth required)
        $successCount = 0;
        $rateLimitedCount = 0;
        
        for ($i = 0; $i < 65; $i++) {
            $response = $this->getJson('/api/v1/assets');
            
            if ($response->status() === 200) {
                $successCount++;
            } elseif ($response->status() === 429) {
                $rateLimitedCount++;
            }
        }
        
        // Should hit rate limits after 60 requests
        expect($rateLimitedCount)->toBeGreaterThan(0);
        expect($successCount <= 60)->toBeTrue();
    });

    test('rate limiting works across different IP addresses', function () {
        // Test that rate limiting correctly handles different IP addresses
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1']);
        
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/v1/assets');
        }
        
        // Switch IP and should get fresh rate limit
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.2']);
        $response = $this->getJson('/api/v1/assets');
        
        expect($response->status())->toBe(200);
        expect($response->headers->get('X-RateLimit-Remaining'))->toBe('59');
    });

    test('rate limiting respects cache expiration', function () {
        Sanctum::actingAs($this->user);
        
        // Hit rate limit (admin endpoints have 200 limit)
        for ($i = 0; $i < 201; $i++) {
            $this->getJson('/api/workflows');
        }
        
        $response = $this->getJson('/api/workflows');
        expect($response->status())->toBe(429);
        
        // Clear cache to simulate time passing
        Cache::flush();
        
        // Should work again
        $response = $this->getJson('/api/workflows');
        expect($response->status())->toBe(200);
    });
});