<?php

namespace Tests\Unit\Jobs;

use App\Jobs\VerifyCgoPayment;
use App\Models\CgoInvestment;
use App\Models\User;
use App\Services\Cgo\PaymentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class VerifyCgoPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentVerificationService $verificationService;
    protected CgoInvestment $investment;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        Queue::fake();
        
        $this->verificationService = Mockery::mock(PaymentVerificationService::class);
        $this->app->instance(PaymentVerificationService::class, $this->verificationService);
        
        $this->user = User::factory()->create();
        $this->investment = CgoInvestment::factory()->create([
            'user_uuid' => $this->user->uuid,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    public function test_skips_already_confirmed_investment(): void
    {
        $this->investment->update(['status' => 'confirmed']);
        
        Log::shouldReceive('info')
            ->once()
            ->with('Investment already confirmed, skipping verification', [
                'investment_id' => $this->investment->id,
            ]);
        
        $this->verificationService->shouldNotReceive('isPaymentExpired');
        $this->verificationService->shouldNotReceive('verifyPayment');
        
        $job = new VerifyCgoPayment($this->investment);
        $job->handle($this->verificationService);
    }

    public function test_marks_expired_payment_as_cancelled(): void
    {
        $this->verificationService->shouldReceive('isPaymentExpired')
            ->once()
            ->with($this->investment)
            ->andReturn(true);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Payment expired, marking as cancelled', [
                'investment_id' => $this->investment->id,
            ]);
        
        $job = new VerifyCgoPayment($this->investment);
        $job->handle($this->verificationService);
        
        $this->investment->refresh();
        $this->assertEquals('cancelled', $this->investment->status);
        $this->assertEquals('expired', $this->investment->payment_status);
        $this->assertEquals('Payment window expired', $this->investment->payment_failure_reason);
        $this->assertNotNull($this->investment->cancelled_at);
    }

    public function test_successfully_verifies_payment(): void
    {
        $this->verificationService->shouldReceive('isPaymentExpired')
            ->once()
            ->with($this->investment)
            ->andReturn(false);
        
        $this->verificationService->shouldReceive('verifyPayment')
            ->once()
            ->with($this->investment)
            ->andReturn(true);
        
        $job = new VerifyCgoPayment($this->investment);
        $job->handle($this->verificationService);
        
        // Should not dispatch retry
        Queue::assertNotPushed(VerifyCgoPayment::class);
    }

    public function test_retries_with_exponential_backoff_on_first_failure(): void
    {
        $this->verificationService->shouldReceive('isPaymentExpired')
            ->once()
            ->andReturn(false);
        
        $this->verificationService->shouldReceive('verifyPayment')
            ->once()
            ->andReturn(false);
        
        Log::shouldReceive('info')
            ->once()
            ->with('Payment not verified, scheduling retry', [
                'investment_id' => $this->investment->id,
                'attempt' => 1,
                'delay' => 300,
            ]);
        
        $job = new VerifyCgoPayment($this->investment, 1);
        $job->handle($this->verificationService);
        
        Queue::assertPushed(function (VerifyCgoPayment $job) {
            return $job->delay === 300; // 5 minutes delay
        });
    }

    public function test_retries_with_longer_delay_on_second_failure(): void
    {
        $this->verificationService->shouldReceive('isPaymentExpired')
            ->once()
            ->andReturn(false);
        
        $this->verificationService->shouldReceive('verifyPayment')
            ->once()
            ->andReturn(false);
        
        Log::shouldReceive('info')
            ->once()
            ->with('Payment not verified, scheduling retry', [
                'investment_id' => $this->investment->id,
                'attempt' => 2,
                'delay' => 600,
            ]);
        
        $job = new VerifyCgoPayment($this->investment, 2);
        $job->handle($this->verificationService);
        
        Queue::assertPushed(function (VerifyCgoPayment $job) {
            return $job->delay === 600; // 10 minutes delay
        });
    }

    public function test_logs_warning_after_max_attempts(): void
    {
        $this->verificationService->shouldReceive('isPaymentExpired')
            ->once()
            ->andReturn(false);
        
        $this->verificationService->shouldReceive('verifyPayment')
            ->once()
            ->andReturn(false);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Payment verification failed after multiple attempts', [
                'investment_id' => $this->investment->id,
                'attempts' => 3,
            ]);
        
        $job = new VerifyCgoPayment($this->investment, 3);
        $job->handle($this->verificationService);
        
        // Should not dispatch retry after 3rd attempt
        Queue::assertNotPushed(VerifyCgoPayment::class);
    }

    public function test_failed_method_updates_investment_status(): void
    {
        $exception = new \Exception('Payment gateway error');
        
        Log::shouldReceive('error')
            ->once()
            ->with('CGO payment verification job failed', Mockery::type('array'));
        
        $job = new VerifyCgoPayment($this->investment);
        $job->failed($exception);
        
        $this->investment->refresh();
        $this->assertEquals('verification_failed', $this->investment->payment_status);
        $this->assertStringContainsString('Manual review required', $this->investment->notes);
    }

    public function test_backoff_returns_correct_delays(): void
    {
        $job = new VerifyCgoPayment($this->investment);
        $backoff = $job->backoff();
        
        $this->assertEquals([60, 300, 900], $backoff);
    }

    public function test_job_has_correct_properties(): void
    {
        $job = new VerifyCgoPayment($this->investment);
        
        $this->assertEquals(5, $job->tries);
        $this->assertEquals(3, $job->maxExceptions);
        $this->assertEquals(120, $job->timeout);
    }

    public function test_job_is_queueable(): void
    {
        $job = new VerifyCgoPayment($this->investment);
        
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    public function test_constructs_with_custom_attempt_number(): void
    {
        $job = new VerifyCgoPayment($this->investment, 2);
        
        // Use reflection to check protected property
        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('attempt');
        $property->setAccessible(true);
        
        $this->assertEquals(2, $property->getValue($job));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}