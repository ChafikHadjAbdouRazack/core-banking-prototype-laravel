<?php

use App\Domain\Webhook\Models\Webhook;
use App\Domain\Webhook\Models\WebhookDelivery;

it('can create a webhook', function () {
    $webhook = Webhook::factory()->create([
        'name' => 'Test Webhook',
        'url' => 'https://example.com/webhook',
        'events' => ['account.created', 'transaction.created'],
    ]);

    expect($webhook->name)->toBe('Test Webhook')
        ->and($webhook->url)->toBe('https://example.com/webhook')
        ->and($webhook->events)->toContain('account.created', 'transaction.created')
        ->and($webhook->is_active)->toBeTrue()
        ->and($webhook->retry_attempts)->toBe(3)
        ->and($webhook->timeout_seconds)->toBe(30);
});

it('can scope active webhooks', function () {
    Webhook::factory()->count(3)->create();
    Webhook::factory()->count(2)->inactive()->create();

    $activeWebhooks = Webhook::active()->get();

    expect($activeWebhooks)->toHaveCount(3);
});

it('can scope webhooks for specific event', function () {
    Webhook::factory()->create(['events' => ['account.created', 'account.updated']]);
    Webhook::factory()->create(['events' => ['transaction.created']]);
    Webhook::factory()->create(['events' => ['account.created', 'transfer.created']]);

    $accountCreatedWebhooks = Webhook::forEvent('account.created')->get();

    expect($accountCreatedWebhooks)->toHaveCount(2);
});

it('can check if webhook is subscribed to event', function () {
    $webhook = Webhook::factory()->create([
        'events' => ['account.created', 'transaction.created'],
    ]);

    expect($webhook->isSubscribedTo('account.created'))->toBeTrue()
        ->and($webhook->isSubscribedTo('transaction.created'))->toBeTrue()
        ->and($webhook->isSubscribedTo('transfer.created'))->toBeFalse();
});

it('can mark webhook as triggered', function () {
    $webhook = Webhook::factory()->create();

    expect($webhook->last_triggered_at)->toBeNull();

    $webhook->markAsTriggered();
    $webhook->refresh();

    expect($webhook->last_triggered_at)->not->toBeNull();
});

it('can mark webhook as successful', function () {
    $webhook = Webhook::factory()->withFailures(5)->create();

    expect($webhook->consecutive_failures)->toBe(5);

    $webhook->markAsSuccessful();
    $webhook->refresh();

    expect($webhook->consecutive_failures)->toBe(0)
        ->and($webhook->last_success_at)->not->toBeNull();
});

it('can mark webhook as failed', function () {
    $webhook = Webhook::factory()->create();

    expect($webhook->consecutive_failures)->toBe(0);

    $webhook->markAsFailed();
    $webhook->refresh();

    expect($webhook->consecutive_failures)->toBe(1)
        ->and($webhook->last_failure_at)->not->toBeNull();
});

it('auto-disables webhook after too many failures', function () {
    $webhook = Webhook::factory()->withFailures(9)->create();

    expect($webhook->is_active)->toBeTrue();

    $webhook->markAsFailed();
    $webhook->refresh();

    expect($webhook->consecutive_failures)->toBe(10)
        ->and($webhook->is_active)->toBeFalse();
});

it('has many deliveries', function () {
    $webhook = Webhook::factory()->create();
    WebhookDelivery::factory()->count(3)->create(['webhook_uuid' => $webhook->uuid]);

    expect($webhook->deliveries)->toHaveCount(3);
});

it('lists all available webhook events', function () {
    expect(Webhook::EVENTS)->toBeArray()
        ->and(array_keys(Webhook::EVENTS))->toContain(
            'account.created',
            'account.updated',
            'account.frozen',
            'account.unfrozen',
            'account.closed',
            'transaction.created',
            'transaction.reversed',
            'transfer.created',
            'transfer.completed',
            'transfer.failed',
            'balance.low',
            'balance.negative'
        );
});
