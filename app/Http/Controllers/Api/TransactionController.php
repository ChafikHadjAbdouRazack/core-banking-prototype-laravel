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
     * @OA\Post(
     *     path="/api/accounts/{uuid}/deposit",
     *     operationId="depositToAccount",
     *     tags={"Transactions"},
     *     summary="Deposit money to an account",
     *     description="Deposits money into a specified account",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="Account UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="integer", example=10000, minimum=1, description="Amount in cents"),
     *             @OA\Property(property="description", type="string", example="Monthly salary", maxLength=255)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Deposit successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="account_uuid", type="string", format="uuid"),
     *                 @OA\Property(property="new_balance", type="integer", example=60000),
     *                 @OA\Property(property="amount_deposited", type="integer", example=10000),
     *                 @OA\Property(property="transaction_type", type="string", example="deposit")
     *             ),
     *             @OA\Property(property="message", type="string", example="Deposit successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot deposit to frozen account",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function deposit(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'sometimes|string|max:255',
        ]);

        $account = Account::where('uuid', $uuid)->firstOrFail();

        if ($account->frozen) {
            return response()->json([
                'message' => 'Cannot deposit to frozen account',
                'error' => 'ACCOUNT_FROZEN',
            ], 422);
        }

        $accountUuid = new AccountUuid($uuid);
        $money = new Money($validated['amount']);

        $workflow = WorkflowStub::make(DepositAccountWorkflow::class);
        $workflow->start($accountUuid, $money);

        $updatedAccount = Account::where('uuid', $uuid)->first();

        return response()->json([
            'data' => [
                'account_uuid' => $updatedAccount->uuid,
                'new_balance' => $updatedAccount->getBalance('USD'),
                'amount_deposited' => $validated['amount'],
                'transaction_type' => 'deposit',
            ],
            'message' => 'Deposit completed successfully',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/accounts/{uuid}/withdraw",
     *     operationId="withdrawFromAccount",
     *     tags={"Transactions"},
     *     summary="Withdraw money from an account",
     *     description="Withdraws money from a specified account",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="Account UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="integer", example=5000, minimum=1, description="Amount in cents"),
     *             @OA\Property(property="description", type="string", example="ATM withdrawal", maxLength=255)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Withdrawal successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="account_uuid", type="string", format="uuid"),
     *                 @OA\Property(property="new_balance", type="integer", example=45000),
     *                 @OA\Property(property="amount_withdrawn", type="integer", example=5000),
     *                 @OA\Property(property="transaction_type", type="string", example="withdrawal")
     *             ),
     *             @OA\Property(property="message", type="string", example="Withdrawal successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Insufficient balance or frozen account",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function withdraw(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'sometimes|string|max:255',
        ]);

        $account = Account::where('uuid', $uuid)->firstOrFail();

        if ($account->frozen) {
            return response()->json([
                'message' => 'Cannot deposit to frozen account',
                'error' => 'ACCOUNT_FROZEN',
            ], 422);
        }

        // For backward compatibility, use USD balance
        $usdBalance = $account->getBalance('USD');
        
        if ($usdBalance < $validated['amount']) {
            return response()->json([
                'message' => 'Insufficient funds',
                'error' => 'INSUFFICIENT_FUNDS',
                'current_balance' => $usdBalance,
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
                'new_balance' => $updatedAccount->getBalance('USD'),
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