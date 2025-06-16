<?php

namespace App\Filament\Admin\Resources\TransactionReadModelResource\Widgets;

use App\Models\TransactionReadModel;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TransactionTypeChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Transaction Types (Last 30 Days)';
    
    protected static ?string $pollingInterval = '60s';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $startDate = Carbon::now()->subDays(30);
        
        $transactions = TransactionReadModel::where('processed_at', '>=', $startDate)
            ->where('status', TransactionReadModel::STATUS_COMPLETED)
            ->selectRaw('type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('type')
            ->get();
        
        $types = [
            TransactionReadModel::TYPE_DEPOSIT => 'Deposits',
            TransactionReadModel::TYPE_WITHDRAWAL => 'Withdrawals',
            TransactionReadModel::TYPE_TRANSFER_IN => 'Transfers In',
            TransactionReadModel::TYPE_TRANSFER_OUT => 'Transfers Out',
        ];
        
        $labels = [];
        $counts = [];
        $totals = [];
        
        foreach ($types as $type => $label) {
            $transaction = $transactions->firstWhere('type', $type);
            $labels[] = $label;
            $counts[] = $transaction ? $transaction->count : 0;
            $totals[] = $transaction ? round($transaction->total / 100, 2) : 0;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Transaction Count',
                    'data' => $counts,
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',  // Green for deposits
                        'rgba(239, 68, 68, 0.8)',   // Red for withdrawals
                        'rgba(59, 130, 246, 0.8)',  // Blue for transfers in
                        'rgba(251, 146, 60, 0.8)',  // Orange for transfers out
                    ],
                    'borderColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                        'rgb(59, 130, 246)',
                        'rgb(251, 146, 60)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.label || "";
                            if (label) {
                                label += ": ";
                            }
                            label += context.parsed + " transactions";
                            return label;
                        }',
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}