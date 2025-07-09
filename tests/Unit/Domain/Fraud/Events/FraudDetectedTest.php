<?php

namespace Tests\Unit\Domain\Fraud\Events;

use App\Domain\Fraud\Events\FraudDetected;
use App\Models\FraudScore;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraudDetectedTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_event_with_fraud_score(): void
    {
        $fraudScore = FraudScore::factory()->create([
            'entity_type' => Transaction::class,
            'score'       => 85,
            'risk_level'  => 'high',
            'action'      => 'block',
        ]);

        $event = new FraudDetected($fraudScore);

        $this->assertSame($fraudScore->id, $event->fraudScore->id);
        $this->assertEquals(85, $event->fraudScore->score);
        $this->assertEquals('high', $event->fraudScore->risk_level);
        $this->assertEquals('block', $event->fraudScore->action);
    }

    public function test_event_uses_required_traits(): void
    {
        $fraudScore = FraudScore::factory()->create();
        $event = new FraudDetected($fraudScore);

        $traits = class_uses($event);

        $this->assertArrayHasKey('Illuminate\Foundation\Events\Dispatchable', $traits);
        $this->assertArrayHasKey('Illuminate\Broadcasting\InteractsWithSockets', $traits);
        $this->assertArrayHasKey('Illuminate\Queue\SerializesModels', $traits);
    }

    public function test_tags_method_returns_correct_tags(): void
    {
        $fraudScore = FraudScore::factory()->create([
            'entity_type' => Transaction::class,
            'risk_level'  => 'medium',
        ]);

        $event = new FraudDetected($fraudScore);
        $tags = $event->tags();

        $this->assertContains('fraud', $tags);
        $this->assertContains('fraud_score:' . $fraudScore->id, $tags);
        $this->assertContains('risk_level:medium', $tags);
        $this->assertContains('entity_type:Transaction', $tags);
    }

    public function test_event_serializes_correctly(): void
    {
        $fraudScore = FraudScore::factory()->create([
            'score'            => 92,
            'risk_level'       => 'high',
            'action'           => 'block',
            'analysis_results' => [
                'rule_engine' => ['score' => 50],
                'behavioral'  => ['score' => 42],
            ],
        ]);

        $event = new FraudDetected($fraudScore);

        // Serialize and unserialize
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertEquals($fraudScore->id, $unserialized->fraudScore->id);
        $this->assertEquals(92, $unserialized->fraudScore->score);
        $this->assertEquals('high', $unserialized->fraudScore->risk_level);
        $this->assertIsArray($unserialized->fraudScore->analysis_results);
    }

    public function test_can_be_dispatched_as_event(): void
    {
        $fraudScore = FraudScore::factory()->create();

        $this->expectsEvents(FraudDetected::class);

        event(new FraudDetected($fraudScore));
    }

    public function test_handles_different_entity_types(): void
    {
        $entityTypes = [
            Transaction::class         => 'Transaction',
            \App\Models\User::class    => 'User',
            \App\Models\Account::class => 'Account',
        ];

        foreach ($entityTypes as $entityClass => $expectedTag) {
            $fraudScore = FraudScore::factory()->create([
                'entity_type' => $entityClass,
                'entity_id'   => 1,
            ]);

            $event = new FraudDetected($fraudScore);
            $tags = $event->tags();

            $this->assertContains("entity_type:{$expectedTag}", $tags);
        }
    }

    public function test_handles_different_risk_levels(): void
    {
        $riskLevels = ['low', 'medium', 'high', 'critical'];

        foreach ($riskLevels as $level) {
            $fraudScore = FraudScore::factory()->create([
                'risk_level' => $level,
            ]);

            $event = new FraudDetected($fraudScore);
            $tags = $event->tags();

            $this->assertContains("risk_level:{$level}", $tags);
        }
    }
}
