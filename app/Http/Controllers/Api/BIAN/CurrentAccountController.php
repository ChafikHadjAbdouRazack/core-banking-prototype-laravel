<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\BIAN;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Services\AccountService;
use App\Domain\Account\Workflows\CreateAccountWorkflow;
use App\Domain\Account\Workflows\DepositAccountWorkflow;
use App\Domain\Account\Workflows\DestroyAccountWorkflow;
use App\Domain\Account\Workflows\FreezeAccountWorkflow;
use App\Domain\Account\Workflows\UnfreezeAccountWorkflow;
use App\Domain\Account\Workflows\WithdrawAccountWorkflow;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Workflow\WorkflowStub;

/**
 * BIAN-compliant Current Account Service Domain Controller
 * 
 * Service Domain: Current Account
 * Functional Pattern: Fulfill
 * Asset Type: Current Account Fulfillment Arrangement
 */
class CurrentAccountController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService
    ) {}

    /**
     * Initiate a new current account fulfillment arrangement
     * 
     * BIAN Operation: Initiate
     * HTTP Method: POST
     * Path: /current-account/{cr-reference-id}/initiate
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customerReference' => 'required|uuid',
            'accountName' => 'required|string|max:255',
            'accountType' => 'required|in:current,checking',
            'initialDeposit' => 'sometimes|integer|min:0',
            'currency' => 'sometimes|string|size:3',
        ]);

        // Generate Control Record Reference ID
        $crReferenceId = Str::uuid()->toString();
        
        // Create the Account data object with the UUID
        $accountData = new \App\Domain\Account\DataObjects\Account(
            uuid: $crReferenceId,
            name: $validated['accountName'],
            userUuid: $validated['customerReference']
        );

        $workflow = WorkflowStub::make(CreateAccountWorkflow::class);
        $workflow->start($accountData);

        // If initial deposit is provided, process it
        if (isset($validated['initialDeposit']) && $validated['initialDeposit'] > 0) {
            $depositWorkflow = WorkflowStub::make(DepositAccountWorkflow::class);
            $depositWorkflow->start(
                new AccountUuid($crReferenceId),
                new Money($validated['initialDeposit'])
            );
        }

        // Create the account record for immediate response
        $account = Account::create([
            'uuid' => $crReferenceId,
            'user_uuid' => $validated['customerReference'],
            'name' => $validated['accountName'],
            'balance' => $validated['initialDeposit'] ?? 0,
        ]);

        return response()->json([
            'currentAccountFulfillmentArrangement' => [
                'crReferenceId' => $crReferenceId,
                'customerReference' => $validated['customerReference'],
                'accountName' => $validated['accountName'],
                'accountType' => $validated['accountType'] ?? 'current',
                'accountStatus' => 'active',
                'accountBalance' => [
                    'amount' => $validated['initialDeposit'] ?? 0,
                    'currency' => $validated['currency'] ?? 'USD',
                ],
                'dateType' => [
                    'date' => now()->toIso8601String(),
                    'dateTypeName' => 'AccountOpeningDate',
                ],
            ],
        ], 201);
    }

    /**
     * Retrieve current account fulfillment arrangement
     * 
     * BIAN Operation: Retrieve
     * HTTP Method: GET
     * Path: /current-account/{cr-reference-id}
     */
    public function retrieve(string $crReferenceId): JsonResponse
    {
        $account = Account::where('uuid', $crReferenceId)->firstOrFail();

        return response()->json([
            'currentAccountFulfillmentArrangement' => [
                'crReferenceId' => $account->uuid,
                'customerReference' => $account->user_uuid,
                'accountName' => $account->name,
                'accountType' => 'current',
                'accountStatus' => 'active',
                'accountBalance' => [
                    'amount' => $account->balance,
                    'currency' => 'USD',
                ],
                'dateType' => [
                    'date' => $account->created_at->toIso8601String(),
                    'dateTypeName' => 'AccountOpeningDate',
                ],
            ],
        ]);
    }

    /**
     * Update current account fulfillment arrangement
     * 
     * BIAN Operation: Update
     * HTTP Method: PUT
     * Path: /current-account/{cr-reference-id}
     */
    public function update(Request $request, string $crReferenceId): JsonResponse
    {
        $account = Account::where('uuid', $crReferenceId)->firstOrFail();

        $validated = $request->validate([
            'accountName' => 'sometimes|string|max:255',
            'accountStatus' => 'sometimes|in:active,dormant,closed',
        ]);

        if (isset($validated['accountName'])) {
            $account->update(['name' => $validated['accountName']]);
        }

        return response()->json([
            'currentAccountFulfillmentArrangement' => [
                'crReferenceId' => $account->uuid,
                'customerReference' => $account->user_uuid,
                'accountName' => $account->name,
                'accountType' => 'current',
                'accountStatus' => $validated['accountStatus'] ?? 'active',
                'updateResult' => 'successful',
            ],
        ]);
    }

    /**
     * Control current account fulfillment arrangement (freeze/unfreeze)
     * 
     * BIAN Operation: Control
     * HTTP Method: PUT
     * Path: /current-account/{cr-reference-id}/control
     */
    public function control(Request $request, string $crReferenceId): JsonResponse
    {
        $account = Account::where('uuid', $crReferenceId)->firstOrFail();

        $validated = $request->validate([
            'controlAction' => 'required|in:freeze,unfreeze,suspend,reactivate',
            'controlReason' => 'required|string|max:500',
        ]);

        $accountUuid = new AccountUuid($crReferenceId);

        switch ($validated['controlAction']) {
            case 'freeze':
            case 'suspend':
                $workflow = WorkflowStub::make(FreezeAccountWorkflow::class);
                $workflow->start($accountUuid, $validated['controlReason'], auth()->user()->name ?? 'System');
                $status = 'frozen';
                break;
            case 'unfreeze':
            case 'reactivate':
                $workflow = WorkflowStub::make(UnfreezeAccountWorkflow::class);
                $workflow->start($accountUuid, $validated['controlReason'], auth()->user()->name ?? 'System');
                $status = 'active';
                break;
        }

        return response()->json([
            'currentAccountFulfillmentControlRecord' => [
                'crReferenceId' => $crReferenceId,
                'controlAction' => $validated['controlAction'],
                'controlReason' => $validated['controlReason'],
                'controlStatus' => $status ?? 'unknown',
                'controlDateTime' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Execute payment from current account (withdrawal)
     * 
     * BIAN Operation: Execute
     * Behavior Qualifier: Payment
     * HTTP Method: POST
     * Path: /current-account/{cr-reference-id}/payment/{bq-reference-id}/execute
     */
    public function executePayment(Request $request, string $crReferenceId): JsonResponse
    {
        $account = Account::where('uuid', $crReferenceId)->firstOrFail();

        $validated = $request->validate([
            'paymentAmount' => 'required|integer|min:1',
            'paymentType' => 'required|in:withdrawal,payment,transfer',
            'paymentDescription' => 'sometimes|string|max:500',
        ]);

        if ($account->balance < $validated['paymentAmount']) {
            return response()->json([
                'paymentExecutionRecord' => [
                    'crReferenceId' => $crReferenceId,
                    'bqReferenceId' => Str::uuid()->toString(),
                    'executionStatus' => 'rejected',
                    'executionReason' => 'Insufficient funds',
                    'accountBalance' => $account->balance,
                    'requestedAmount' => $validated['paymentAmount'],
                ],
            ], 422);
        }

        $accountUuid = new AccountUuid($crReferenceId);
        $money = new Money($validated['paymentAmount']);

        $workflow = WorkflowStub::make(WithdrawAccountWorkflow::class);
        $workflow->start($accountUuid, $money);

        $account->refresh();

        return response()->json([
            'paymentExecutionRecord' => [
                'crReferenceId' => $crReferenceId,
                'bqReferenceId' => Str::uuid()->toString(),
                'executionStatus' => 'completed',
                'paymentAmount' => $validated['paymentAmount'],
                'paymentType' => $validated['paymentType'],
                'paymentDescription' => $validated['paymentDescription'] ?? null,
                'accountBalance' => $account->balance,
                'executionDateTime' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Execute deposit to current account
     * 
     * BIAN Operation: Execute
     * Behavior Qualifier: Deposit
     * HTTP Method: POST
     * Path: /current-account/{cr-reference-id}/deposit/{bq-reference-id}/execute
     */
    public function executeDeposit(Request $request, string $crReferenceId): JsonResponse
    {
        $account = Account::where('uuid', $crReferenceId)->firstOrFail();

        $validated = $request->validate([
            'depositAmount' => 'required|integer|min:1',
            'depositType' => 'required|in:cash,check,transfer,direct',
            'depositDescription' => 'sometimes|string|max:500',
        ]);

        $accountUuid = new AccountUuid($crReferenceId);
        $money = new Money($validated['depositAmount']);

        $workflow = WorkflowStub::make(DepositAccountWorkflow::class);
        $workflow->start($accountUuid, $money);

        $account->refresh();

        return response()->json([
            'depositExecutionRecord' => [
                'crReferenceId' => $crReferenceId,
                'bqReferenceId' => Str::uuid()->toString(),
                'executionStatus' => 'completed',
                'depositAmount' => $validated['depositAmount'],
                'depositType' => $validated['depositType'],
                'depositDescription' => $validated['depositDescription'] ?? null,
                'accountBalance' => $account->balance,
                'executionDateTime' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Retrieve account balance
     * 
     * BIAN Operation: Retrieve
     * Behavior Qualifier: AccountBalance
     * HTTP Method: GET
     * Path: /current-account/{cr-reference-id}/account-balance/{bq-reference-id}/retrieve
     */
    public function retrieveAccountBalance(string $crReferenceId): JsonResponse
    {
        $account = Account::where('uuid', $crReferenceId)->firstOrFail();

        return response()->json([
            'accountBalanceRecord' => [
                'crReferenceId' => $crReferenceId,
                'bqReferenceId' => Str::uuid()->toString(),
                'balanceAmount' => $account->balance,
                'balanceCurrency' => 'USD',
                'balanceType' => 'available',
                'balanceDateTime' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Retrieve transaction report
     * 
     * BIAN Operation: Retrieve
     * Behavior Qualifier: TransactionReport
     * HTTP Method: GET
     * Path: /current-account/{cr-reference-id}/transaction-report/{bq-reference-id}/retrieve
     */
    public function retrieveTransactionReport(Request $request, string $crReferenceId): JsonResponse
    {
        $account = Account::where('uuid', $crReferenceId)->firstOrFail();

        $validated = $request->validate([
            'fromDate' => 'sometimes|date',
            'toDate' => 'sometimes|date|after_or_equal:fromDate',
            'transactionType' => 'sometimes|in:all,credit,debit',
        ]);

        // Query stored events for transaction history
        $query = \DB::table('stored_events')
            ->where('aggregate_uuid', $crReferenceId)
            ->whereIn('event_class', [
                'App\Domain\Account\Events\MoneyAdded',
                'App\Domain\Account\Events\MoneySubtracted',
            ]);

        if (isset($validated['fromDate'])) {
            $query->where('created_at', '>=', $validated['fromDate']);
        }

        if (isset($validated['toDate'])) {
            $query->where('created_at', '<=', $validated['toDate']);
        }

        $events = $query->orderBy('created_at', 'desc')->get();

        $transactions = $events->map(function ($event) {
            $properties = json_decode($event->event_properties, true);
            $eventClass = class_basename($event->event_class);
            
            return [
                'transactionReference' => $event->aggregate_uuid,
                'transactionType' => $eventClass === 'MoneyAdded' ? 'credit' : 'debit',
                'transactionAmount' => $properties['money']['amount'] ?? 0,
                'transactionDateTime' => $event->created_at,
                'transactionDescription' => $eventClass === 'MoneyAdded' ? 'Deposit' : 'Withdrawal',
            ];
        });

        if (isset($validated['transactionType']) && $validated['transactionType'] !== 'all') {
            $transactions = $transactions->filter(function ($transaction) use ($validated) {
                return $transaction['transactionType'] === $validated['transactionType'];
            });
        }

        return response()->json([
            'transactionReportRecord' => [
                'crReferenceId' => $crReferenceId,
                'bqReferenceId' => Str::uuid()->toString(),
                'reportPeriod' => [
                    'fromDate' => $validated['fromDate'] ?? $account->created_at->toDateString(),
                    'toDate' => $validated['toDate'] ?? now()->toDateString(),
                ],
                'transactions' => $transactions->values(),
                'transactionCount' => $transactions->count(),
                'reportDateTime' => now()->toIso8601String(),
            ],
        ]);
    }
}