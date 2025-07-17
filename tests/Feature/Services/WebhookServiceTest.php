<?php

use App\Domain\Webhook\Jobs\ProcessWebhookDelivery;
use App\Domain\Webhook\Models\Webhook;
use App\Domain\Webhook\Models\WebhookDelivery;
use App\Domain\Webhook\Services\WebhookService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->webhookService = app(WebhookService::class);
    Queue::fake();
});

it('dispatches webhook to subscribed webhooks', function () {
    $webhook1 = Webhook::factory()->create(['events' => ['account.created', 'account.updated']]);
    $webhook2 = Webhook::factory()->create(['events' => ['account.created']]);
    $webhook3 = Webhook::factory()->create(['events' => ['transaction.created']]);

    $this->webhookService->dispatch('account.created', [
        'account_uuid' => 'test-uuid',
        'name' => 'Test Account',
    ]);

    // Should create deliveries for webhook1 and webhook2 only
    expect(WebhookDelivery::count())->toBe(2);

    $deliveries = WebhookDelivery::all();
    $webhookUuids = $deliveries->pluck('webhook_uuid')->toArray();

    expect($webhookUuids)->toContain($webhook1->uuid, $webhook2->uuid)
        ->and($webhookUuids)->not->toContain($webhook3->uuid);

    // Should queue the deliveries
    Queue::assertPushed(ProcessWebhookDelivery::class, 2);
});

it('does not dispatch to inactive webhooks', function () {
    Webhook::factory()->create(['events' => ['account.created'], 'is_active' => true]);
    Webhook::factory()->inactive()->create(['events' => ['account.created']]);

    $this->webhookService->dispatch('account.created', ['test' => 'data']);

    expect(WebhookDelivery::count())->toBe(1);
    Queue::assertPushed(ProcessWebhookDelivery::class, 1);
});

it('includes event and timestamp in payload', function () {
    Webhook::factory()->create(['events' => ['test.event']]);

    $this->webhookService->dispatch('test.event', ['custom' => 'data']);

    $delivery = WebhookDelivery::first();

    expect($delivery->payload)->toHaveKey('event', 'test.event')
        ->and($delivery->payload)->toHaveKey('timestamp')
        ->and($delivery->payload)->toHaveKey('custom', 'data');
});

it('dispatches account events', function () {
    Webhook::factory()->create(['events' => ['account.frozen']]);

    $this->webhookService->dispatchAccountEvent('account.frozen', 'account-uuid', [
        'reason' => 'Suspicious activity',
    ]);

    $delivery = WebhookDelivery::first();

    expect($delivery->event_type)->toBe('account.frozen')
        ->and($delivery->payload)->toHaveKey('account_uuid', 'account-uuid')
        ->and($delivery->payload)->toHaveKey('reason', 'Suspicious activity');
});

it('dispatches transaction events', function () {
    Webhook::factory()->create(['events' => ['transaction.created']]);

    $this->webhookService->dispatchTransactionEvent('transaction.created', [
        'account_uuid' => 'test-uuid',
        'type' => 'deposit',
        'amount' => 1000,
    ]);

    $delivery = WebhookDelivery::first();

    expect($delivery->event_type)->toBe('transaction.created')
        ->and($delivery->payload['account_uuid'])->toBe('test-uuid')
        ->and($delivery->payload['type'])->toBe('deposit')
        ->and($delivery->payload['amount'])->toBe(1000);
});

it('dispatches transfer events', function () {
    Webhook::factory()->create(['events' => ['transfer.created']]);

    $this->webhookService->dispatchTransferEvent('transfer.created', [
        'from_account_uuid' => 'from-uuid',
        'to_account_uuid' => 'to-uuid',
        'amount' => 5000,
    ]);

    $delivery = WebhookDelivery::first();

    expect($delivery->event_type)->toBe('transfer.created')
        ->and($delivery->payload['from_account_uuid'])->toBe('from-uuid')
        ->and($delivery->payload['to_account_uuid'])->toBe('to-uuid')
        ->and($delivery->payload['amount'])->toBe(5000);
});

it('generates webhook signature correctly', function () {
    $payload = '{"test": "data"}';
    $secret = 'webhook-secret';

    $signature = $this->webhookService->generateSignature($payload, $secret);

    expect($signature)->toStartWith('sha256=')
        ->and($signature)->toBe('sha256=' . hash_hmac('sha256', $payload, $secret));
});

it('verifies webhook signature correctly', function () {
    $payload = '{"test": "data"}';
    $secret = 'webhook-secret';
    $validSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    $invalidSignature = 'sha256=invalid';

    expect($this->webhookService->verifySignature($payload, $validSignature, $secret))->toBeTrue()
        ->and($this->webhookService->verifySignature($payload, $invalidSignature, $secret))->toBeFalse();
});

it('handles no webhooks gracefully', function () {
    // No webhooks exist
    $this->webhookService->dispatch('account.created', ['test' => 'data']);

    expect(WebhookDelivery::count())->toBe(0);
    Queue::assertNothingPushed();
});
