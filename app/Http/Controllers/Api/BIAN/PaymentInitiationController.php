<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\BIAN;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Payment\Workflows\TransferWorkflow;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Workflow\WorkflowStub;

/**
 * BIAN-compliant Payment Initiation Service Domain Controller
 * 
 * Service Domain: Payment Initiation
 * Functional Pattern: Transact
 * Asset Type: Payment Transaction
 */
class PaymentInitiationController extends Controller
{
    /**
     * Initiate a new payment transaction
     * 
     * BIAN Operation: Initiate
     * HTTP Method: POST
     * Path: /payment-initiation/{cr-reference-id}/initiate
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payerReference' => 'required|uuid|exists:accounts,uuid',
            'payeeReference' => 'required|uuid|exists:accounts,uuid|different:payerReference',
            'paymentAmount' => 'required|integer|min:1',
            'paymentCurrency' => 'sometimes|string|size:3',
            'paymentPurpose' => 'sometimes|string|max:500',
            'paymentType' => 'required|in:internal,external,instant,scheduled',
            'valueDate' => 'sometimes|date|after_or_equal:today',
        ]);

        // Validate payer has sufficient funds
        $payerAccount = Account::where('uuid', $validated['payerReference'])->first();
        $payeeAccount = Account::where('uuid', $validated['payeeReference'])->first();

        // For backward compatibility, use USD balance
        $payerBalance = $payerAccount->getBalance('USD');
        
        if ($payerBalance < $validated['paymentAmount']) {
            return response()->json([
                'paymentInitiationTransaction' => [
                    'crReferenceId' => Str::uuid()->toString(),
                    'paymentStatus' => 'rejected',
                    'statusReason' => 'Insufficient funds',
                    'payerAvailableBalance' => $payerBalance,
                    'requestedAmount' => $validated['paymentAmount'],
                ],
            ], 422);
        }

        // Generate Control Record Reference ID
        $crReferenceId = Str::uuid()->toString();

        // Execute payment if immediate
        if ($validated['paymentType'] !== 'scheduled' || !isset($validated['valueDate'])) {
            $fromUuid = new AccountUuid($validated['payerReference']);
            $toUuid = new AccountUuid($validated['payeeReference']);
            $money = new Money($validated['paymentAmount']);

            try {
                $workflow = WorkflowStub::make(TransferWorkflow::class);
                $workflow->start($fromUuid, $toUuid, $money);
                $status = 'completed';
            } catch (\Exception $e) {
                $status = 'failed';
            }
        } else {
            $status = 'scheduled';
        }

        $payerAccount->refresh();
        $payeeAccount->refresh();

        return response()->json([
            'paymentInitiationTransaction' => [
                'crReferenceId' => $crReferenceId,
                'paymentStatus' => $status,
                'paymentDetails' => [
                    'payerReference' => $validated['payerReference'],
                    'payerName' => $payerAccount->name,
                    'payeeReference' => $validated['payeeReference'],
                    'payeeName' => $payeeAccount->name,
                    'paymentAmount' => $validated['paymentAmount'],
                    'paymentCurrency' => $validated['paymentCurrency'] ?? 'USD',
                    'paymentPurpose' => $validated['paymentPurpose'] ?? null,
                    'paymentType' => $validated['paymentType'],
                ],
                'paymentSchedule' => [
                    'initiationDate' => now()->toIso8601String(),
                    'valueDate' => $validated['valueDate'] ?? now()->toDateString(),
                ],
                'balanceAfterPayment' => [
                    'payerBalance' => $payerAccount->getBalance('USD'),
                    'payeeBalance' => $payeeAccount->getBalance('USD'),
                ],
            ],
        ], 201);
    }

    /**
     * Update a payment transaction
     * 
     * BIAN Operation: Update
     * HTTP Method: PUT
     * Path: /payment-initiation/{cr-reference-id}/update
     */
    public function update(Request $request, string $crReferenceId): JsonResponse
    {
        $validated = $request->validate([
            'paymentStatus' => 'required|in:cancelled,suspended,resumed',
            'statusReason' => 'required|string|max:500',
        ]);

        // In a real implementation, this would update the payment record
        // For now, we'll return a simulated response
        return response()->json([
            'paymentInitiationTransaction' => [
                'crReferenceId' => $crReferenceId,
                'updateAction' => $validated['paymentStatus'],
                'updateReason' => $validated['statusReason'],
                'updateStatus' => 'successful',
                'updateDateTime' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Retrieve payment transaction details
     * 
     * BIAN Operation: Retrieve
     * HTTP Method: GET
     * Path: /payment-initiation/{cr-reference-id}/retrieve
     */
    public function retrieve(string $crReferenceId): JsonResponse
    {
        // Query stored events for transfer details
        $event = \DB::table('stored_events')
            ->where('event_class', 'App\Domain\Account\Events\MoneyTransferred')
            ->where('aggregate_uuid', $crReferenceId)
            ->first();

        if (!$event) {
            abort(404, 'Payment transaction not found');
        }

        $properties = json_decode($event->event_properties, true);

        return response()->json([
            'paymentInitiationTransaction' => [
                'crReferenceId' => $crReferenceId,
                'paymentStatus' => 'completed',
                'paymentDetails' => [
                    'payerReference' => $properties['from_uuid'] ?? $event->aggregate_uuid,
                    'payeeReference' => $properties['to_uuid'] ?? null,
                    'paymentAmount' => $properties['money']['amount'] ?? 0,
                    'paymentCurrency' => 'USD',
                ],
                'paymentSchedule' => [
                    'initiationDate' => $event->created_at,
                    'completionDate' => $event->created_at,
                ],
                'paymentReference' => $properties['hash']['hash'] ?? null,
            ],
        ]);
    }

    /**
     * Execute payment transaction
     * 
     * BIAN Operation: Execute
     * HTTP Method: POST
     * Path: /payment-initiation/{cr-reference-id}/execute
     */
    public function execute(Request $request, string $crReferenceId): JsonResponse
    {
        $validated = $request->validate([
            'executionMode' => 'required|in:immediate,retry,force',
        ]);

        // In a real implementation, this would execute a scheduled/pending payment
        return response()->json([
            'paymentExecutionRecord' => [
                'crReferenceId' => $crReferenceId,
                'executionMode' => $validated['executionMode'],
                'executionStatus' => 'completed',
                'executionDateTime' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Request payment status
     * 
     * BIAN Operation: Request
     * Behavior Qualifier: PaymentStatus
     * HTTP Method: POST
     * Path: /payment-initiation/{cr-reference-id}/payment-status/{bq-reference-id}/request
     */
    public function requestPaymentStatus(string $crReferenceId): JsonResponse
    {
        // Query for payment events
        $events = \DB::table('stored_events')
            ->where('aggregate_uuid', $crReferenceId)
            ->whereIn('event_class', [
                'App\Domain\Account\Events\MoneyTransferred',
                'App\Domain\Account\Events\MoneyAdded',
                'App\Domain\Account\Events\MoneySubtracted',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $status = $events->isEmpty() ? 'not_found' : 'completed';

        return response()->json([
            'paymentStatusRecord' => [
                'crReferenceId' => $crReferenceId,
                'bqReferenceId' => Str::uuid()->toString(),
                'paymentStatus' => $status,
                'statusCheckDateTime' => now()->toIso8601String(),
                'eventCount' => $events->count(),
            ],
        ]);
    }

    /**
     * Retrieve payment history
     * 
     * BIAN Operation: Retrieve
     * Behavior Qualifier: PaymentHistory
     * HTTP Method: GET
     * Path: /payment-initiation/{cr-reference-id}/payment-history/{bq-reference-id}/retrieve
     */
    public function retrievePaymentHistory(Request $request, string $accountReference): JsonResponse
    {
        $account = Account::where('uuid', $accountReference)->firstOrFail();

        $validated = $request->validate([
            'fromDate' => 'sometimes|date',
            'toDate' => 'sometimes|date|after_or_equal:fromDate',
            'paymentDirection' => 'sometimes|in:sent,received,all',
        ]);

        // Query stored events for payment history
        $query = \DB::table('stored_events')
            ->where('event_class', 'App\Domain\Account\Events\MoneyTransferred')
            ->where(function ($q) use ($accountReference) {
                $q->where('aggregate_uuid', $accountReference)
                  ->orWhereRaw("JSON_EXTRACT(event_properties, '$.to_uuid') = ?", [$accountReference])
                  ->orWhereRaw("JSON_EXTRACT(event_properties, '$.from_uuid') = ?", [$accountReference]);
            });

        if (isset($validated['fromDate'])) {
            $query->where('created_at', '>=', $validated['fromDate']);
        }

        if (isset($validated['toDate'])) {
            $query->where('created_at', '<=', $validated['toDate']);
        }

        $events = $query->orderBy('created_at', 'desc')->get();

        $payments = $events->map(function ($event) use ($accountReference) {
            $properties = json_decode($event->event_properties, true);
            $fromUuid = $properties['from_uuid'] ?? $event->aggregate_uuid;
            $toUuid = $properties['to_uuid'] ?? null;
            
            return [
                'paymentReference' => $event->aggregate_uuid,
                'paymentDirection' => $fromUuid === $accountReference ? 'sent' : 'received',
                'payerReference' => $fromUuid,
                'payeeReference' => $toUuid,
                'paymentAmount' => $properties['money']['amount'] ?? 0,
                'paymentDateTime' => $event->created_at,
                'paymentHash' => $properties['hash']['hash'] ?? null,
            ];
        });

        if (isset($validated['paymentDirection']) && $validated['paymentDirection'] !== 'all') {
            $payments = $payments->filter(function ($payment) use ($validated) {
                return $payment['paymentDirection'] === $validated['paymentDirection'];
            });
        }

        return response()->json([
            'paymentHistoryRecord' => [
                'accountReference' => $accountReference,
                'bqReferenceId' => Str::uuid()->toString(),
                'historyPeriod' => [
                    'fromDate' => $validated['fromDate'] ?? $account->created_at->toDateString(),
                    'toDate' => $validated['toDate'] ?? now()->toDateString(),
                ],
                'payments' => $payments->values(),
                'paymentCount' => $payments->count(),
                'retrievalDateTime' => now()->toIso8601String(),
            ],
        ]);
    }
}