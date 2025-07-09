<?php

namespace Tests\Unit\Listeners;

use App\Domain\Custodian\Events\CustodianHealthChanged;
use App\Domain\Custodian\Services\BankAlertingService;
use App\Listeners\HandleCustodianHealthChange;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class HandleCustodianHealthChangeTest extends TestCase
{
    private BankAlertingService $alertingService;

    private HandleCustodianHealthChange $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alertingService = Mockery::mock(BankAlertingService::class);
        $this->listener = new HandleCustodianHealthChange($this->alertingService);
    }

    public function test_handles_custodian_health_change_event(): void
    {
        $event = new CustodianHealthChanged(
            custodian: 'test-custodian',
            previousHealth: 'healthy',
            currentHealth: 'unhealthy',
            metadata: ['reason' => 'Connection timeout']
        );

        $this->alertingService->shouldReceive('handleHealthChange')
            ->once()
            ->with($event);

        $this->listener->handle($event);
    }

    public function test_handles_different_health_states(): void
    {
        $healthStates = [
            ['healthy', 'unhealthy'],
            ['unhealthy', 'healthy'],
            ['healthy', 'degraded'],
            ['degraded', 'healthy'],
            ['degraded', 'unhealthy'],
        ];

        foreach ($healthStates as [$previous, $current]) {
            $event = new CustodianHealthChanged(
                custodian: 'test-custodian',
                previousHealth: $previous,
                currentHealth: $current,
                metadata: []
            );

            $this->alertingService->shouldReceive('handleHealthChange')
                ->once()
                ->with($event);

            $this->listener->handle($event);
        }
    }

    public function test_failed_method_logs_error(): void
    {
        $event = new CustodianHealthChanged(
            custodian: 'failed-custodian',
            previousHealth: 'healthy',
            currentHealth: 'unhealthy',
            metadata: ['test' => 'data']
        );

        $exception = new \RuntimeException('Alert service unavailable');

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to handle custodian health change', [
                'custodian' => 'failed-custodian',
                'error'     => 'Alert service unavailable',
            ]);

        $this->listener->failed($event, $exception);
    }

    public function test_is_queued_listener(): void
    {
        $this->assertArrayHasKey(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            class_implements($this->listener)
        );
    }

    public function test_uses_queue_interactions(): void
    {
        $this->assertArrayHasKey(
            \Illuminate\Queue\InteractsWithQueue::class,
            class_uses($this->listener)
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
