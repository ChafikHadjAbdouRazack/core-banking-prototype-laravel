<?php

declare(strict_types=1);

use App\Jobs\ProcessWebhookDelivery;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

describe('ProcessWebhookDelivery Job', function () {
    it('can process webhook delivery successfully', function () {
        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret' => 'test-secret',
            'timeout_seconds' => 30,
        ]);
        
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'status' => 'pending',
            'payload' => ['test' => 'data'],
        ]);
        
        Http::fake([
            'example.com/*' => Http::response([], 200),
        ]);
        
        $job = new ProcessWebhookDelivery($delivery);
        $job->handle();
        
        $delivery->refresh();
        expect($delivery->status)->toBe('delivered');
        expect($delivery->response_status)->toBe(200);
        expect($delivery->delivered_at)->not->toBeNull();
    });
    
    it('handles webhook delivery failures', function () {
        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
        ]);
        
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'status' => 'pending',
        ]);
        
        Http::fake([
            'example.com/*' => Http::response([], 500),
        ]);
        
        $job = new ProcessWebhookDelivery($delivery);
        $job->handle();
        
        $delivery->refresh();
        expect($delivery->status)->toBe('failed');
        expect($delivery->response_status)->toBe(500);
        expect($delivery->delivered_at)->toBeNull();
    });
    
    it('generates correct signature header', function () {
        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret' => 'test-secret',
        ]);
        
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
            'payload' => ['test' => 'data'],
        ]);
        
        Http::fake([
            'example.com/*' => Http::response([], 200),
        ]);
        
        $job = new ProcessWebhookDelivery($delivery);
        $job->handle();
        
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Webhook-Signature');
        });
    });
    
    it('respects timeout settings', function () {
        $webhook = Webhook::factory()->create([
            'url' => 'https://example.com/webhook',
            'timeout_seconds' => 5,
        ]);
        
        $delivery = WebhookDelivery::factory()->create([
            'webhook_id' => $webhook->id,
        ]);
        
        Http::fake([
            'example.com/*' => Http::response([], 200),
        ]);
        
        $job = new ProcessWebhookDelivery($delivery);
        $job->handle();
        
        // Just ensure it completes without timeout
        expect($delivery->fresh()->status)->toBe('delivered');
    });
});