<?php

use App\Domain\Custodian\Services\WebhookVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new WebhookVerificationService();
});

it('verifies valid paysera signature', function () {
    $payload = '{"event":"payment.completed","amount":1000}';
    $secret = 'test-paysera-secret';
    $signature = hash_hmac('sha256', $payload, $secret);
    
    config(['custodians.connectors.paysera.webhook_secret' => $secret]);
    
    $result = $this->service->verifySignature('paysera', $payload, $signature);
    
    expect($result)->toBeTrue();
});

it('rejects invalid paysera signature', function () {
    $payload = '{"event":"payment.completed","amount":1000}';
    $secret = 'test-paysera-secret';
    $signature = 'invalid-signature';
    
    config(['custodians.connectors.paysera.webhook_secret' => $secret]);
    
    $result = $this->service->verifySignature('paysera', $payload, $signature);
    
    expect($result)->toBeFalse();
});

it('rejects paysera signature when secret not configured', function () {
    $payload = '{"event":"payment.completed"}';
    $signature = 'any-signature';
    
    config(['custodians.connectors.paysera.webhook_secret' => null]);
    
    $result = $this->service->verifySignature('paysera', $payload, $signature);
    
    expect($result)->toBeFalse();
});

it('verifies valid santander signature with timestamp', function () {
    $timestamp = '1234567890';
    $payload = '{"event_type":"transfer.completed"}';
    $secret = 'test-santander-secret';
    $dataToSign = $timestamp . '.' . $payload;
    $signature = hash_hmac('sha512', $dataToSign, $secret);
    
    config(['custodians.connectors.santander.webhook_secret' => $secret]);
    
    $headers = ['X-Santander-Timestamp' => $timestamp];
    $result = $this->service->verifySignature('santander', $payload, $signature, $headers);
    
    expect($result)->toBeTrue();
});

it('rejects santander signature without timestamp', function () {
    $payload = '{"event_type":"transfer.completed"}';
    $secret = 'test-santander-secret';
    $signature = hash_hmac('sha512', $payload, $secret);
    
    config(['custodians.connectors.santander.webhook_secret' => $secret]);
    
    $headers = []; // No timestamp
    $result = $this->service->verifySignature('santander', $payload, $signature, $headers);
    
    expect($result)->toBeFalse();
});

it('always accepts mock webhooks', function () {
    $result = $this->service->verifySignature('mock', 'any-payload', 'any-signature');
    
    expect($result)->toBeTrue();
});

it('rejects unknown custodian', function () {
    $result = $this->service->verifySignature('unknown', 'payload', 'signature');
    
    expect($result)->toBeFalse();
});

it('validates timestamp within tolerance', function () {
    $currentTime = time();
    
    // Valid timestamp (1 minute ago)
    expect($this->service->isTimestampValid($currentTime - 60))->toBeTrue();
    
    // Valid timestamp (1 minute in future - allows for clock drift)
    expect($this->service->isTimestampValid($currentTime + 60))->toBeTrue();
    
    // Invalid timestamp (10 minutes ago)
    expect($this->service->isTimestampValid($currentTime - 600))->toBeFalse();
    
    // Custom tolerance
    expect($this->service->isTimestampValid($currentTime - 400, 500))->toBeTrue();
    expect($this->service->isTimestampValid($currentTime - 600, 500))->toBeFalse();
});

it('uses constant-time comparison for signatures', function () {
    $payload = '{"test":true}';
    $secret = 'secret';
    $correctSignature = hash_hmac('sha256', $payload, $secret);
    
    config(['custodians.connectors.paysera.webhook_secret' => $secret]);
    
    // This should use hash_equals internally which is timing-safe
    $result1 = $this->service->verifySignature('paysera', $payload, $correctSignature);
    $result2 = $this->service->verifySignature('paysera', $payload, 'wrong-signature');
    
    expect($result1)->toBeTrue();
    expect($result2)->toBeFalse();
});