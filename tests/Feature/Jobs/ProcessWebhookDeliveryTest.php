<?php

declare(strict_types=1);

use App\Jobs\ProcessWebhookDelivery;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

describe('ProcessWebhookDelivery Job', function () {
    it('can process webhook delivery successfully', function () {
        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret' => 'test-secret',
            'timeout_seconds' => 30,
            'is_active' => true,
        ]);
        
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'status' => 'pending',
            'payload' => ['test' => 'data'],
        ]);
        
        Http::fake([
            'example.com/*' => Http::response(['success' => true], 200),
        ]);
        
        $webhookService = app(WebhookService::class);
        $job = new ProcessWebhookDelivery($delivery);
        $job->handle($webhookService);
        
        $delivery->refresh();
        expect($delivery->status)->toBe('delivered');
        expect($delivery->response_status)->toBe(200);
        expect($delivery->delivered_at)->not->toBeNull();
    });
    
    it('skips delivery for inactive webhook', function () {
        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
            'is_active' => false,
        ]);
        
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'status' => 'pending',
        ]);
        
        Log::spy();
        
        $webhookService = app(WebhookService::class);
        $job = new ProcessWebhookDelivery($delivery);
        $job->handle($webhookService);
        
        Log::shouldHaveReceived('warning')
            ->with("Skipping delivery for inactive webhook: {$webhook->uuid}");
        
        Http::assertNothingSent();
    });
    
    it('handles webhook delivery failures with HTTP error', function () {
        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
            'is_active' => true,
        ]);
        
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'status' => 'pending',
        ]);
        
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);
        
        $webhookService = app(WebhookService::class);
        $job = new ProcessWebhookDelivery($delivery);
        
        expect(fn() => $job->handle($webhookService))
            ->toThrow(Exception::class);
        
        $delivery->refresh();
        expect($delivery->status)->toBe('failed');
        expect($delivery->response_status)->toBe(500);
        expect($delivery->delivered_at)->toBeNull();
    });
    
    it('handles connection exceptions', function () {
        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
            'is_active' => true,
        ]);
        
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'status' => 'pending',
        ]);
        
        Http::fake([
            'example.com/*' => function () {
                throw new ConnectionException('Connection timeout');
            },
        ]);
        
        $webhookService = app(WebhookService::class);
        $job = new ProcessWebhookDelivery($delivery);
        
        expect(fn() => $job->handle($webhookService))
            ->toThrow(ConnectionException::class);
        
        $delivery->refresh();
        expect($delivery->status)->toBe('failed');
        expect($delivery->error_message)->toContain('Connection timeout');
    });
    
    it('generates correct signature header when secret is provided', function () {
        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret' => 'test-secret',
            'is_active' => true,
        ]);
        
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'payload' => ['test' => 'data'],
        ]);
        
        Http::fake([
            'example.com/*' => Http::response([], 200),
        ]);
        
        $webhookService = app(WebhookService::class);
        $job = new ProcessWebhookDelivery($delivery);
        $job->handle($webhookService);
        
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Webhook-Signature');
        });
    });
    
    it('does not add signature header when no secret is provided', function () {
        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret' => null,
            'is_active' => true,
        ]);
        
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'payload' => ['test' => 'data'],
        ]);
        
        Http::fake([
            'example.com/*' => Http::response([], 200),
        ]);
        
        $webhookService = app(WebhookService::class);
        $job = new ProcessWebhookDelivery($delivery);
        $job->handle($webhookService);
        
        Http::assertSent(function ($request) {
            return !$request->hasHeader('X-Webhook-Signature');
        });
    });
    
    it('includes all required headers', function () {
        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
            'headers' => ['X-Custom' => 'custom-value'],
            'is_active' => true,
        ]);
        
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'event_type' => 'account.created',
        ]);
        
        Http::fake([
            'example.com/*' => Http::response([], 200),
        ]);
        
        $webhookService = app(WebhookService::class);
        $job = new ProcessWebhookDelivery($delivery);
        $job->handle($webhookService);
        
        Http::assertSent(function ($request) use ($webhook, $delivery) {
            return $request->hasHeader('Content-Type', 'application/json') &&
                   $request->hasHeader('User-Agent', 'FinAegis-Webhook/1.0') &&
                   $request->hasHeader('X-Webhook-ID', $webhook->uuid) &&
                   $request->hasHeader('X-Webhook-Event', $delivery->event_type) &&
                   $request->hasHeader('X-Webhook-Delivery', $delivery->uuid) &&
                   $request->hasHeader('X-Custom', 'custom-value');
        });
    });
    
    it('can get retry until time', function () {
        $webhook = Webhook::factory()->create();
        $delivery = WebhookDelivery::factory()->create(['webhook_id' => $webhook->id]);
        
        $job = new ProcessWebhookDelivery($delivery);
        $retryUntil = $job->retryUntil();
        
        expect($retryUntil)->toBeInstanceOf(DateTime::class);
        expect($retryUntil->getTimestamp())->toBeGreaterThan(now()->addHours(23)->getTimestamp());
    });
    
    it('can get backoff times', function () {
        $webhook = Webhook::factory()->create();
        $delivery = WebhookDelivery::factory()->create(['webhook_id' => $webhook->id]);
        
        $job = new ProcessWebhookDelivery($delivery);
        $backoff = $job->backoff();
        
        expect($backoff)->toBe([60, 300, 900]);
    });
    
    it('handles job failure', function () {
        $webhook = Webhook::factory()->create();
        $delivery = WebhookDelivery::factory()->create(['webhook_id' => $webhook->id]);
        
        Log::spy();
        
        $job = new ProcessWebhookDelivery($delivery);
        $exception = new Exception('Test failure');
        $job->failed($exception);
        
        Log::shouldHaveReceived('error')
            ->with('Webhook delivery job failed permanently', [
                'delivery_uuid' => $delivery->uuid,
                'error' => 'Test failure',
            ]);
    });
    
    it('respects timeout settings', function () {
        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
            'timeout_seconds' => 5,
            'is_active' => true,
        ]);
        
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
        ]);
        
        Http::fake([
            'example.com/*' => Http::response([], 200),
        ]);
        
        $webhookService = app(WebhookService::class);
        $job = new ProcessWebhookDelivery($delivery);
        $job->handle($webhookService);
        
        Http::assertSent(function ($request) {
            return $request->timeout === 5;
        });
        
        expect($delivery->fresh()->status)->toBe('delivered');
    });
});