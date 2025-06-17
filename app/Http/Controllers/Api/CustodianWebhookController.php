<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Custodian\Services\WebhookVerificationService;
use App\Http\Controllers\Controller;
use App\Models\CustodianWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CustodianWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookVerificationService $verificationService
    ) {}

    /**
     * Handle incoming webhook from Paysera.
     */
    public function paysera(Request $request): JsonResponse
    {
        return $this->handleWebhook($request, 'paysera');
    }

    /**
     * Handle incoming webhook from Santander.
     */
    public function santander(Request $request): JsonResponse
    {
        return $this->handleWebhook($request, 'santander');
    }

    /**
     * Handle incoming webhook from Mock Bank.
     */
    public function mock(Request $request): JsonResponse
    {
        return $this->handleWebhook($request, 'mock');
    }

    /**
     * Common webhook handler.
     */
    private function handleWebhook(Request $request, string $custodianName): JsonResponse
    {
        $payload = $request->getContent();
        $headers = $request->headers->all();
        
        // Extract signature based on custodian
        $signature = match ($custodianName) {
            'paysera' => $request->header('X-Paysera-Signature', ''),
            'santander' => $request->header('X-Santander-Signature', ''),
            'mock' => 'mock-signature',
            default => '',
        };
        
        // Convert header arrays to single values for webhook verification
        $cleanHeaders = [];
        foreach ($headers as $key => $value) {
            $cleanHeaders[$key] = is_array($value) ? $value[0] : $value;
        }

        // Verify signature
        if (!$this->verificationService->verifySignature($custodianName, $payload, $signature, $cleanHeaders)) {
            Log::warning('Invalid webhook signature', [
                'custodian' => $custodianName,
                'signature' => $signature,
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Parse the payload
        $data = json_decode($payload, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid webhook payload', [
                'custodian' => $custodianName,
                'error' => json_last_error_msg(),
            ]);
            
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // Extract event information
        $eventType = $this->extractEventType($custodianName, $data);
        $eventId = $this->extractEventId($custodianName, $data);

        // Store webhook for processing
        try {
            $webhook = CustodianWebhook::create([
                'custodian_name' => $custodianName,
                'event_type' => $eventType,
                'event_id' => $eventId,
                'headers' => $headers,
                'payload' => $data,
                'signature' => $signature,
                'status' => 'pending',
            ]);

            // Dispatch job to process webhook asynchronously
            dispatch(new \App\Jobs\ProcessCustodianWebhook($webhook->uuid));

            Log::info('Webhook received and queued', [
                'custodian' => $custodianName,
                'event_type' => $eventType,
                'webhook_id' => $webhook->id,
            ]);

            return response()->json(['status' => 'accepted'], 202);
        } catch (\Illuminate\Database\QueryException $e) {
            // Check if it's a duplicate key violation
            if ($e->getCode() === '23000') {
                Log::info('Duplicate webhook received', [
                    'custodian' => $custodianName,
                    'event_id' => $eventId,
                ]);
                
                // Return success to prevent webhook provider from retrying
                return response()->json(['status' => 'accepted', 'duplicate' => true], 202);
            }
            
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to store webhook', [
                'custodian' => $custodianName,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Extract event type based on custodian format.
     */
    private function extractEventType(string $custodianName, array $data): string
    {
        return match ($custodianName) {
            'paysera' => $data['event'] ?? 'unknown',
            'santander' => $data['event_type'] ?? 'unknown',
            'mock' => $data['type'] ?? 'unknown',
            default => 'unknown',
        };
    }

    /**
     * Extract event ID based on custodian format.
     */
    private function extractEventId(string $custodianName, array $data): ?string
    {
        return match ($custodianName) {
            'paysera' => $data['event_id'] ?? null,
            'santander' => $data['id'] ?? null,
            'mock' => $data['id'] ?? null,
            default => null,
        };
    }
}