<?php

namespace Tests\Unit\Domain\Banking\Events;

use App\Domain\Banking\Events\DepositCompleted;
use App\Domain\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositCompletedTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_event_with_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'type'   => 'deposit',
            'amount' => 10000,
            'status' => 'completed',
        ]);

        $event = new DepositCompleted($transaction);

        $this->assertSame($transaction->id, $event->transaction->id);
        $this->assertEquals('deposit', $event->transaction->type);
        $this->assertEquals(10000, $event->transaction->amount);
        $this->assertEquals('completed', $event->transaction->status);
    }

    public function test_event_uses_required_traits(): void
    {
        $transaction = Transaction::factory()->create();
        $event = new DepositCompleted($transaction);

        // Check that the event uses the required traits
        $traits = class_uses($event);

        $this->assertArrayHasKey('Illuminate\Foundation\Events\Dispatchable', $traits);
        $this->assertArrayHasKey('Illuminate\Broadcasting\InteractsWithSockets', $traits);
        $this->assertArrayHasKey('Illuminate\Queue\SerializesModels', $traits);
    }

    public function test_event_serializes_transaction_model(): void
    {
        $transaction = Transaction::factory()->create([
            'reference' => 'DEP-123456',
            'amount'    => 25000,
            'currency'  => 'USD',
        ]);

        $event = new DepositCompleted($transaction);

        // Serialize and unserialize to test SerializesModels trait
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertEquals($transaction->id, $unserialized->transaction->id);
        $this->assertEquals('DEP-123456', $unserialized->transaction->reference);
        $this->assertEquals(25000, $unserialized->transaction->amount);
        $this->assertEquals('USD', $unserialized->transaction->currency);
    }

    public function test_event_can_be_dispatched(): void
    {
        $transaction = Transaction::factory()->create();

        // Test that event can be dispatched (uses Dispatchable trait)
        $this->expectsEvents(DepositCompleted::class);

        event(new DepositCompleted($transaction));
    }

    public function test_handles_transaction_with_metadata(): void
    {
        $transaction = Transaction::factory()->create([
            'type'     => 'deposit',
            'amount'   => 50000,
            'metadata' => [
                'source'         => 'bank_transfer',
                'bank_reference' => 'BANK-REF-789',
                'depositor_name' => 'John Doe',
            ],
        ]);

        $event = new DepositCompleted($transaction);

        $this->assertEquals('bank_transfer', $event->transaction->metadata['source']);
        $this->assertEquals('BANK-REF-789', $event->transaction->metadata['bank_reference']);
        $this->assertEquals('John Doe', $event->transaction->metadata['depositor_name']);
    }

    public function test_handles_different_transaction_statuses(): void
    {
        $statuses = ['completed', 'processing', 'confirmed'];

        foreach ($statuses as $status) {
            $transaction = Transaction::factory()->create([
                'type'   => 'deposit',
                'status' => $status,
            ]);

            $event = new DepositCompleted($transaction);

            $this->assertEquals($status, $event->transaction->status);
        }
    }

    public function test_preserves_transaction_relationships(): void
    {
        $transaction = Transaction::factory()
            ->hasAccountFrom(1)
            ->hasAccountTo(1)
            ->create([
                'type'   => 'deposit',
                'amount' => 100000,
            ]);

        $event = new DepositCompleted($transaction);

        // Load relationships
        $event->transaction->load(['accountFrom', 'accountTo']);

        $this->assertNotNull($event->transaction->accountFrom);
        $this->assertNotNull($event->transaction->accountTo);
    }
}
