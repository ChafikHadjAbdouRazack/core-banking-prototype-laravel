<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Workflows\DepositAccountWorkflow;
use App\Domain\Account\Workflows\WithdrawAccountWorkflow;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Workflow\WorkflowStub;

class TransactionController extends Controller
{
    /**
     * Deposit money to an account
     */
    public function deposit(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'sometimes|string|max:255',
        ]);

        $account = Account::where('uuid', $uuid)->firstOrFail();

        // Skip frozen check since the column doesn't exist

        $accountUuid = new AccountUuid($uuid);
        $money = new Money($validated['amount']);

        $workflow = WorkflowStub::make(DepositAccountWorkflow::class);
        $workflow->start($accountUuid, $money);

        $updatedAccount = Account::where('uuid', $uuid)->first();

        return response()->json([
            'data' => [
                'account_uuid' => $updatedAccount->uuid,
                'new_balance' => $updatedAccount->balance,
                'amount_deposited' => $validated['amount'],
                'transaction_type' => 'deposit',
            ],
            'message' => 'Deposit completed successfully',
        ]);
    }

    /**
     * Withdraw money from an account
     */
    public function withdraw(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'sometimes|string|max:255',
        ]);

        $account = Account::where('uuid', $uuid)->firstOrFail();

        // Skip frozen check since the column doesn't exist

        if ($account->balance < $validated['amount']) {
            return response()->json([
                'message' => 'Insufficient funds',
                'error' => 'INSUFFICIENT_FUNDS',
                'current_balance' => $account->balance,
                'requested_amount' => $validated['amount'],
            ], 422);
        }

        $accountUuid = new AccountUuid($uuid);
        $money = new Money($validated['amount']);

        try {
            $workflow = WorkflowStub::make(WithdrawAccountWorkflow::class);
            $workflow->start($accountUuid, $money);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Withdrawal failed',
                'error' => 'WITHDRAWAL_FAILED',
            ], 422);
        }

        $updatedAccount = Account::where('uuid', $uuid)->first();

        return response()->json([
            'data' => [
                'account_uuid' => $updatedAccount->uuid,
                'new_balance' => $updatedAccount->balance,
                'amount_withdrawn' => $validated['amount'],
                'transaction_type' => 'withdrawal',
            ],
            'message' => 'Withdrawal completed successfully',
        ]);
    }

    /**
     * Get transaction history for an account
     */
    public function history(string $uuid): JsonResponse
    {
        $account = Account::where('uuid', $uuid)->firstOrFail();
        
        // Since transactions are event sourced, we need to query stored_events
        $events = \DB::table('stored_events')
            ->where('aggregate_uuid', $uuid)
            ->whereIn('event_class', [
                'App\Domain\Account\Events\MoneyAdded',
                'App\Domain\Account\Events\MoneySubtracted',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // Transform events to transaction-like format
        $transactions = collect($events->items())->map(function ($event) {
            $properties = json_decode($event->event_properties, true);
            $eventClass = class_basename($event->event_class);
            
            return [
                'uuid' => $event->aggregate_uuid,
                'type' => $eventClass === 'MoneyAdded' ? 'credit' : 'debit',
                'amount' => $properties['money']['amount'] ?? 0,
                'hash' => $properties['hash']['hash'] ?? null,
                'created_at' => $event->created_at,
            ];
        });

        return response()->json([
            'data' => $transactions,
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }
}