<?php

namespace Tests\Unit\Domain\Compliance\Events;

use App\Domain\Compliance\Events\SuspiciousActivityDetected;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuspiciousActivityDetectedTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_event_with_transaction_and_alerts(): void
    {
        $transaction = Transaction::factory()->create([
            'amount' => 100000,
            'type' => 'wire_transfer',
        ]);

        $alerts = [
            [
                'type' => 'large_transaction',
                'severity' => 'high',
                'message' => 'Transaction amount exceeds threshold',
                'threshold' => 50000,
                'actual' => 100000,
            ],
            [
                'type' => 'velocity_check',
                'severity' => 'medium',
                'message' => 'Multiple transactions in short period',
                'count' => 5,
                'period' => '1 hour',
            ],
        ];

        $event = new SuspiciousActivityDetected($transaction, $alerts);

        $this->assertSame($transaction->id, $event->transaction->id);
        $this->assertEquals($alerts, $event->alerts);
        $this->assertCount(2, $event->alerts);
    }

    public function test_event_uses_required_traits(): void
    {
        $transaction = Transaction::factory()->create();
        $event = new SuspiciousActivityDetected($transaction, []);

        $traits = class_uses($event);
        
        $this->assertArrayHasKey('Illuminate\Foundation\Events\Dispatchable', $traits);
        $this->assertArrayHasKey('Illuminate\Broadcasting\InteractsWithSockets', $traits);
        $this->assertArrayHasKey('Illuminate\Queue\SerializesModels', $traits);
    }

    public function test_event_properties_are_readonly(): void
    {
        $transaction = Transaction::factory()->create();
        $alerts = [['type' => 'test']];
        
        $event = new SuspiciousActivityDetected($transaction, $alerts);

        // Properties are readonly, attempting to modify should cause error
        $this->expectError();
        $event->transaction = Transaction::factory()->create();
    }

    public function test_event_serializes_correctly(): void
    {
        $transaction = Transaction::factory()->create([
            'reference' => 'SUSP-123',
            'amount' => 75000,
        ]);
        
        $alerts = [
            ['type' => 'pattern_match', 'pattern' => 'unusual_destination'],
        ];

        $event = new SuspiciousActivityDetected($transaction, $alerts);
        
        // Serialize and unserialize
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertEquals($transaction->id, $unserialized->transaction->id);
        $this->assertEquals('SUSP-123', $unserialized->transaction->reference);
        $this->assertEquals($alerts, $unserialized->alerts);
    }

    public function test_handles_empty_alerts_array(): void
    {
        $transaction = Transaction::factory()->create();
        
        $event = new SuspiciousActivityDetected($transaction, []);

        $this->assertEmpty($event->alerts);
        $this->assertIsArray($event->alerts);
    }

    public function test_handles_complex_alert_structures(): void
    {
        $transaction = Transaction::factory()->create();
        
        $complexAlerts = [
            [
                'type' => 'ml_detection',
                'model' => 'fraud_detector_v2',
                'confidence' => 0.89,
                'features' => [
                    'amount_zscore' => 3.2,
                    'time_since_last' => 120,
                    'destination_risk' => 'high',
                ],
                'metadata' => [
                    'model_version' => '2.1.0',
                    'training_date' => '2024-01-01',
                ],
            ],
            [
                'type' => 'rule_based',
                'rules_triggered' => ['R001', 'R045', 'R102'],
                'combined_score' => 85,
                'action_required' => 'manual_review',
            ],
        ];

        $event = new SuspiciousActivityDetected($transaction, $complexAlerts);

        $this->assertEquals($complexAlerts, $event->alerts);
        $this->assertEquals(0.89, $event->alerts[0]['confidence']);
        $this->assertCount(3, $event->alerts[1]['rules_triggered']);
    }

    public function test_can_be_dispatched_as_event(): void
    {
        $transaction = Transaction::factory()->create();
        $alerts = [['type' => 'test_alert']];

        $this->expectsEvents(SuspiciousActivityDetected::class);
        
        event(new SuspiciousActivityDetected($transaction, $alerts));
    }
}