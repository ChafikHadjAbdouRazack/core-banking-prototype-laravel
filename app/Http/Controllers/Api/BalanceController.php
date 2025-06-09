<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\Workflows\BalanceInquiryWorkflow;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Turnover;
use Illuminate\Http\JsonResponse;
use Workflow\WorkflowStub;

class BalanceController extends Controller
{
    /**
     * Get account balance using the BalanceInquiryWorkflow
     */
    public function show(string $uuid): JsonResponse
    {
        $account = Account::where('uuid', $uuid)->firstOrFail();
        
        $accountUuid = new AccountUuid($uuid);
        
        // For now, just use the account balance directly
        // The workflow would typically be used in a more complex scenario
        $balance = $account->balance;

        $turnover = Turnover::where('account_uuid', $uuid)
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'data' => [
                'account_uuid' => $uuid,
                'balance' => $balance,
                'frozen' => false, // Default to false since the column doesn't exist
                'last_updated' => $account->updated_at,
                'turnover' => $turnover ? [
                    'debit' => $turnover->debit,
                    'credit' => $turnover->credit,
                    'period_start' => $turnover->created_at,
                    'period_end' => $turnover->updated_at,
                ] : null,
            ],
        ]);
    }

    /**
     * Get account balance summary with statistics
     */
    public function summary(string $uuid): JsonResponse
    {
        $account = Account::where('uuid', $uuid)->firstOrFail();
        
        $turnovers = Turnover::where('account_uuid', $uuid)
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get();

        $totalDebit = $turnovers->sum('debit');
        $totalCredit = $turnovers->sum('credit');
        $averageMonthlyDebit = $turnovers->count() > 0 ? $totalDebit / $turnovers->count() : 0;
        $averageMonthlyCredit = $turnovers->count() > 0 ? $totalCredit / $turnovers->count() : 0;

        return response()->json([
            'data' => [
                'account_uuid' => $uuid,
                'current_balance' => $account->balance,
                'frozen' => false, // Default to false since the column doesn't exist
                'statistics' => [
                    'total_debit_12_months' => $totalDebit,
                    'total_credit_12_months' => $totalCredit,
                    'average_monthly_debit' => (int) $averageMonthlyDebit,
                    'average_monthly_credit' => (int) $averageMonthlyCredit,
                    'months_analyzed' => $turnovers->count(),
                ],
                'monthly_turnovers' => $turnovers->map(function ($turnover) {
                    return [
                        'month' => $turnover->created_at->format('Y-m'),
                        'debit' => $turnover->debit,
                        'credit' => $turnover->credit,
                        'net' => $turnover->credit - $turnover->debit,
                    ];
                }),
            ],
        ]);
    }
}