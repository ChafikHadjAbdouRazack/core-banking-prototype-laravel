<?php

use App\Models\Webhook;
use App\Models\WebhookDelivery;

it('can create a webhook delivery', function () {
    $webhook = Webhook::factory()->create();
    $delivery = WebhookDelivery::factory()->create([
        'webhook_uuid' => $webhook->uuid,
        'event_type'   => 'account.created',
    ]);

    expect($delivery->webhook_uuid)->toBe($webhook->uuid)
        ->and($delivery->event_type)->toBe('account.created')
        ->and($delivery->status)->toBe(WebhookDelivery::STATUS_PENDING)
        ->and($delivery->attempt_number)->toBe(1);
});

it('belongs to a webhook', function () {
    $webhook = Webhook::factory()->create();
    $delivery = WebhookDelivery::factory()->create(['webhook_uuid' => $webhook->uuid]);

    expect($delivery->webhook->uuid)->toBe($webhook->uuid);
});

it('can scope pending deliveries', function () {
    WebhookDelivery::factory()->count(2)->create();
    WebhookDelivery::factory()->count(3)->delivered()->create();
    WebhookDelivery::factory()->count(1)->failed()->create();

    $pendingDeliveries = WebhookDelivery::pending()->get();

    expect($pendingDeliveries)->toHaveCount(2);
});

it('can scope failed deliveries', function () {
    WebhookDelivery::factory()->count(2)->create();
    WebhookDelivery::factory()->count(3)->delivered()->create();
    WebhookDelivery::factory()->count(4)->failed()->create();

    $failedDeliveries = WebhookDelivery::failed()->get();

    expect($failedDeliveries)->toHaveCount(4);
});

it('can scope deliveries ready for retry', function () {
    WebhookDelivery::factory()->failed()->create(['next_retry_at' => now()->subMinute()]);
    WebhookDelivery::factory()->failed()->create(['next_retry_at' => now()->addMinute()]);
    WebhookDelivery::factory()->failed()->create(['next_retry_at' => null]);

    $readyForRetry = WebhookDelivery::readyForRetry()->get();

    expect($readyForRetry)->toHaveCount(1);
});

it('can mark delivery as delivered', function () {
    $webhook = Webhook::factory()->create();
    $delivery = WebhookDelivery::factory()->create(['webhook_uuid' => $webhook->uuid]);

    $delivery->markAsDelivered(
        statusCode: 200,
        responseBody: '{"success": true}',
        responseHeaders: ['Content-Type' => 'application/json'],
        durationMs: 150
    );

    $delivery->refresh();

    expect($delivery->status)->toBe(WebhookDelivery::STATUS_DELIVERED)
        ->and($delivery->response_status)->toBe(200)
        ->and($delivery->response_body)->toBe('{"success": true}')
        ->and($delivery->response_headers)->toBe(['Content-Type' => 'application/json'])
        ->and($delivery->duration_ms)->toBe(150)
        ->and($delivery->delivered_at)->not->toBeNull();

    // Also check that webhook was marked as successful
    $webhook->refresh();
    expect($webhook->last_success_at)->not->toBeNull()
        ->and($webhook->consecutive_failures)->toBe(0);
});

it('can mark delivery as failed with retry', function () {
    $webhook = Webhook::factory()->create(['retry_attempts' => 3]);
    $delivery = WebhookDelivery::factory()->create([
        'webhook_uuid'   => $webhook->uuid,
        'attempt_number' => 1,
    ]);

    $delivery->markAsFailed(
        errorMessage: 'Connection timeout',
        statusCode: 0
    );

    $delivery->refresh();

    expect($delivery->status)->toBe(WebhookDelivery::STATUS_FAILED)
        ->and($delivery->error_message)->toBe('Connection timeout')
        ->and($delivery->response_status)->toBe(0)
        ->and($delivery->next_retry_at)->not->toBeNull()
        ->and($delivery->next_retry_at)->toBeGreaterThan(now());

    // Check that webhook was marked as failed
    $webhook->refresh();
    expect($webhook->last_failure_at)->not->toBeNull()
        ->and($webhook->consecutive_failures)->toBe(1);
});

it('does not set retry for final attempt', function () {
    $webhook = Webhook::factory()->create(['retry_attempts' => 3]);
    $delivery = WebhookDelivery::factory()->create([
        'webhook_uuid'   => $webhook->uuid,
        'attempt_number' => 3,
    ]);

    $delivery->markAsFailed('Final failure');

    $delivery->refresh();

    expect($delivery->status)->toBe(WebhookDelivery::STATUS_FAILED)
        ->and($delivery->next_retry_at)->toBeNull();
});

it('can create a retry delivery', function () {
    $delivery = WebhookDelivery::factory()->create([
        'attempt_number' => 2,
        'event_type'     => 'test.event',
        'payload'        => ['test' => 'data'],
    ]);

    $retry = $delivery->createRetry();

    expect($retry->webhook_uuid)->toBe($delivery->webhook_uuid)
        ->and($retry->event_type)->toBe($delivery->event_type)
        ->and($retry->payload)->toBe($delivery->payload)
        ->and($retry->attempt_number)->toBe(3)
        ->and($retry->status)->toBe(WebhookDelivery::STATUS_PENDING);
});
