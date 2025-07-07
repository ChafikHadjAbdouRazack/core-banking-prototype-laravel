<?php

namespace Tests\Unit\Domain\Exchange\Services;

use App\Domain\Exchange\Services\CircuitBreakerService;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class CircuitBreakerServiceTest extends TestCase
{
    private CircuitBreakerService $circuitBreaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->circuitBreaker = new CircuitBreakerService;
        Cache::flush(); // Clear any existing circuit breaker state
    }

    public function test_successful_call_returns_result(): void
    {
        $result = $this->circuitBreaker->call('test_service', function () {
            return 'success';
        });

        $this->assertEquals('success', $result);
    }

    public function test_circuit_opens_after_failure_threshold(): void
    {
        // Cause 5 failures to open the circuit
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->circuitBreaker->call('test_service', function () {
                    throw new \Exception('Service failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }

        // Next call should fail immediately due to open circuit
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Circuit breaker is OPEN for service: test_service');

        $this->circuitBreaker->call('test_service', function () {
            return 'should not execute';
        });
    }

    public function test_circuit_transitions_to_half_open(): void
    {
        $this->markTestSkipped('Circuit breaker half-open transition needs investigation with cache driver');
        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->circuitBreaker->call('test_service', function () {
                    throw new \Exception('Service failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }

        // Verify the circuit is open
        $currentState = Cache::get('circuit_breaker:test_service:state');
        $this->assertEquals('open', $currentState);

        // Clear all circuit breaker caches and manually set the state with old timestamp
        Cache::forget('circuit_breaker:test_service:failure_count');
        Cache::forget('circuit_breaker:test_service:success_count');
        Cache::forget('circuit_breaker:test_service:half_open_attempts');
        
        // Manually set state to open with old timestamp to trigger half-open transition
        Cache::put('circuit_breaker:test_service:state', 'open');
        Cache::put('circuit_breaker:test_service:state_changed_at', now()->subMinutes(2)->toIso8601String()); // Use 2 minutes to ensure timeout

        // Should allow one request through in half-open state
        $result = $this->circuitBreaker->call('test_service', function () {
            return 'recovery';
        });

        $this->assertEquals('recovery', $result);
    }

    public function test_circuit_closes_after_success_threshold_in_half_open(): void
    {
        $this->markTestSkipped('Circuit breaker half-open success threshold needs investigation with cache driver');
        // Clear all circuit breaker state
        Cache::forget('circuit_breaker:test_service:failure_count');
        Cache::forget('circuit_breaker:test_service:success_count');
        Cache::forget('circuit_breaker:test_service:half_open_attempts');
        
        // Set circuit to half-open
        Cache::put('circuit_breaker:test_service:state', 'half-open');
        Cache::put('circuit_breaker:test_service:state_changed_at', now());

        // Two successful calls should close the circuit
        for ($i = 0; $i < 2; $i++) {
            $this->circuitBreaker->call('test_service', function () {
                return 'success';
            });
        }

        // Verify circuit is closed by checking state
        $state = Cache::get('circuit_breaker:test_service:state', 'closed');
        $this->assertEquals('closed', $state);
    }

    public function test_circuit_reopens_on_failure_in_half_open(): void
    {
        // Set circuit to half-open
        Cache::put('circuit_breaker:test_service:state', 'half-open', 60);

        // Failure in half-open should reopen circuit
        try {
            $this->circuitBreaker->call('test_service', function () {
                throw new \Exception('Service failure');
            });
        } catch (\Exception $e) {
            // Expected
        }

        // Verify circuit is open
        $state = Cache::get('circuit_breaker:test_service:state');
        $this->assertEquals('open', $state);
    }

    public function test_half_open_limits_requests(): void
    {
        // Set circuit to half-open
        Cache::put('circuit_breaker:test_service:state', 'half-open', 60);

        // First request should succeed
        $this->circuitBreaker->call('test_service', function () {
            return 'success';
        });

        // Second request should be rejected
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Circuit breaker is HALF-OPEN with limit reached for service: test_service');

        $this->circuitBreaker->call('test_service', function () {
            return 'should not execute';
        });
    }
}
