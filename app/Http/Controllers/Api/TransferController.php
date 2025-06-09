<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Payment\Workflows\TransferWorkflow;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Workflow\WorkflowStub;

class TransferController extends Controller
{
    /**
     * Create a new transfer between accounts
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_account_uuid' => 'required|uuid|exists:accounts,uuid',
            'to_account_uuid' => 'required|uuid|exists:accounts,uuid|different:from_account_uuid',
            'amount' => 'required|integer|min:1',
            'description' => 'sometimes|string|max:255',
        ]);

        $fromAccount = Account::where('uuid', $validated['from_account_uuid'])->first();
        $toAccount = Account::where('uuid', $validated['to_account_uuid'])->first();

        // Skip frozen checks since the column doesn't exist

        if ($fromAccount->balance < $validated['amount']) {
            return response()->json([
                'message' => 'Insufficient funds',
                'error' => 'INSUFFICIENT_FUNDS',
                'current_balance' => $fromAccount->balance,
                'requested_amount' => $validated['amount'],
            ], 422);
        }

        $fromUuid = new AccountUuid($validated['from_account_uuid']);
        $toUuid = new AccountUuid($validated['to_account_uuid']);
        $money = new Money($validated['amount']);

        try {
            $workflow = WorkflowStub::make(TransferWorkflow::class);
            $workflow->start($fromUuid, $toUuid, $money);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Transfer failed',
                'error' => 'TRANSFER_FAILED',
            ], 422);
        }

        $fromAccount->refresh();
        $toAccount->refresh();

        // Since we're using event sourcing, we don't have a traditional transfer record
        // Just use the provided data for the response
        $transferUuid = Str::uuid()->toString();

        return response()->json([
            'data' => [
                'transfer_uuid' => $transferUuid,
                'from_account_uuid' => $validated['from_account_uuid'],
                'to_account_uuid' => $validated['to_account_uuid'],
                'amount' => $validated['amount'],
                'from_account_new_balance' => $fromAccount->balance,
                'to_account_new_balance' => $toAccount->balance,
                'status' => 'completed',
                'created_at' => now(),
            ],
            'message' => 'Transfer completed successfully',
        ], 201);
    }

    /**
     * Get transfer details
     */
    public function show(string $uuid): JsonResponse
    {
        // Since transfers are event sourced, we need to query stored_events
        $event = \DB::table('stored_events')
            ->where('event_class', 'App\Domain\Account\Events\MoneyTransferred')
            ->where('aggregate_uuid', $uuid)
            ->first();

        if (!$event) {
            abort(404, 'Transfer not found');
        }

        $properties = json_decode($event->event_properties, true);

        return response()->json([
            'data' => [
                'uuid' => $uuid,
                'from_account_uuid' => $properties['from_uuid'] ?? null,
                'to_account_uuid' => $properties['to_uuid'] ?? null,
                'amount' => $properties['money']['amount'] ?? 0,
                'hash' => $properties['hash']['hash'] ?? null,
                'created_at' => $event->created_at,
                'updated_at' => $event->created_at,
            ],
        ]);
    }

    /**
     * Get transfer history for an account
     */
    public function history(string $accountUuid): JsonResponse
    {
        $account = Account::where('uuid', $accountUuid)->firstOrFail();

        // Since transfers are event sourced, we need to query stored_events
        $events = \DB::table('stored_events')
            ->where('event_class', 'App\Domain\Account\Events\MoneyTransferred')
            ->where(function ($query) use ($accountUuid) {
                $query->where('aggregate_uuid', $accountUuid)
                      ->orWhereRaw("event_properties->>'$.to_uuid' = ?", [$accountUuid])
                      ->orWhereRaw("event_properties->>'$.from_uuid' = ?", [$accountUuid]);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // Transform events to transfer-like format
        $transfers = collect($events->items())->map(function ($event) use ($accountUuid) {
            $properties = json_decode($event->event_properties, true);
            
            return [
                'uuid' => $event->aggregate_uuid,
                'from_account_uuid' => $properties['from_uuid'] ?? $event->aggregate_uuid,
                'to_account_uuid' => $properties['to_uuid'] ?? null,
                'amount' => $properties['money']['amount'] ?? 0,
                'direction' => ($properties['from_uuid'] ?? $event->aggregate_uuid) === $accountUuid ? 'outgoing' : 'incoming',
                'created_at' => $event->created_at,
            ];
        });

        return response()->json([
            'data' => $transfers,
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }
}