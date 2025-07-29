<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ValidateWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ValidateWebhookSignatureTest extends TestCase
{
    private ValidateWebhookSignature $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ValidateWebhookSignature();
    }

    /** @test */
    public function it_validates_stripe_signature_format()
    {
        Config::shouldReceive('get')
            ->with('services.stripe.webhook_secret')
            ->andReturn('test_secret');

        $payload = '{"test": "data"}';
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'test_secret');
        $stripeSignature = 't=' . $timestamp . ',v1=' . $signature;

        $request = Request::create('/stripe/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $stripeSignature,
        ], $payload);

        $next = function ($request) {
            return response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next, 'stripe');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_rejects_invalid_stripe_signature()
    {
        Config::shouldReceive('get')
            ->with('services.stripe.webhook_secret')
            ->andReturn('test_secret');

        Log::shouldReceive('warning')->once();

        $payload = '{"test": "data"}';
        $timestamp = time();
        $stripeSignature = 't=' . $timestamp . ',v1=invalid_signature';

        $request = Request::create('/stripe/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $stripeSignature,
        ], $payload);

        $next = function ($request) {
            return response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next, 'stripe');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"Invalid signature"}', $response->getContent());
    }

    /** @test */
    public function it_validates_coinbase_signature()
    {
        Config::shouldReceive('get')
            ->with('services.coinbase_commerce.webhook_secret')
            ->andReturn('test_secret');

        $payload = '{"event": {"type": "charge:confirmed"}}';
        $signature = hash_hmac('sha256', $payload, 'test_secret');

        $request = Request::create('/api/webhooks/coinbase', 'POST', [], [], [], [
            'HTTP_X_CC_WEBHOOK_SIGNATURE' => $signature,
        ], $payload);

        $next = function ($request) {
            return response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next, 'coinbase');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_validates_paysera_signature()
    {
        Config::shouldReceive('get')
            ->with('custodians.connectors.paysera.webhook_secret')
            ->andReturn('test_secret');

        $payload = '{"event": "transaction.completed"}';
        $signature = hash_hmac('sha256', $payload, 'test_secret');

        $request = Request::create('/api/webhooks/paysera', 'POST', [], [], [], [
            'HTTP_X_PAYSERA_SIGNATURE' => $signature,
        ], $payload);

        $next = function ($request) {
            return response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next, 'paysera');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_validates_santander_signature_with_timestamp()
    {
        Config::shouldReceive('get')
            ->with('custodians.connectors.santander.webhook_secret')
            ->andReturn('test_secret');

        $payload = '{"event_type": "payment.received"}';
        $timestamp = (string) time();
        $dataToSign = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha512', $dataToSign, 'test_secret');

        $request = Request::create('/api/webhooks/santander', 'POST', [], [], [], [
            'HTTP_X_SANTANDER_SIGNATURE' => $signature,
            'HTTP_X_SANTANDER_TIMESTAMP' => $timestamp,
        ], $payload);

        $next = function ($request) {
            return response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next, 'santander');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_rejects_expired_timestamps()
    {
        Config::shouldReceive('get')
            ->with('services.stripe.webhook_secret')
            ->andReturn('test_secret');

        Log::shouldReceive('warning')->once();

        $payload = '{"test": "data"}';
        $timestamp = time() - 400; // 400 seconds ago
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'test_secret');
        $stripeSignature = 't=' . $timestamp . ',v1=' . $signature;

        $request = Request::create('/stripe/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $stripeSignature,
        ], $payload);

        $next = function ($request) {
            return response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next, 'stripe');

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_rejects_unknown_provider()
    {
        Log::shouldReceive('warning')->once();

        $request = Request::create('/webhook/unknown', 'POST');

        $next = function ($request) {
            return response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next, 'unknown_provider');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"Invalid signature"}', $response->getContent());
    }

    /** @test */
    public function it_handles_missing_configuration()
    {
        Config::shouldReceive('get')
            ->with('services.stripe.webhook_secret')
            ->andReturn(null);

        Log::shouldReceive('warning')->once();

        $request = Request::create('/stripe/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 'some_signature',
        ]);

        $next = function ($request) {
            return response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next, 'stripe');

        $this->assertEquals(403, $response->getStatusCode());
    }
}