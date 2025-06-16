<?php

namespace App\Filament\Admin\Resources\TransactionReadModelResource\Widgets;

use App\Models\TransactionReadModel;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class TransactionStatsWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $todayTransactions = TransactionReadModel::whereDate('processed_at', today())->get();
        $yesterdayTransactions = TransactionReadModel::whereDate('processed_at', today()->subDay())->get();
        
        $todayVolume = $todayTransactions->sum('amount');
        $yesterdayVolume = $yesterdayTransactions->sum('amount');
        
        $todayCount = $todayTransactions->count();
        $yesterdayCount = $yesterdayTransactions->count();
        
        $pendingCount = TransactionReadModel::where('status', TransactionReadModel::STATUS_PENDING)->count();
        $failedCount = TransactionReadModel::where('status', TransactionReadModel::STATUS_FAILED)
            ->whereDate('created_at', '>=', today()->subDays(7))
            ->count();
        
        // Calculate deposits and withdrawals for today
        $todayDeposits = $todayTransactions->whereIn('type', [
            TransactionReadModel::TYPE_DEPOSIT,
            TransactionReadModel::TYPE_TRANSFER_IN
        ])->sum('amount');
        
        $todayWithdrawals = $todayTransactions->whereIn('type', [
            TransactionReadModel::TYPE_WITHDRAWAL,
            TransactionReadModel::TYPE_TRANSFER_OUT
        ])->sum('amount');
        
        $netFlow = $todayDeposits - $todayWithdrawals;
        
        return [
            Stat::make('Today\'s Volume', '$' . Number::format($todayVolume / 100, 2))
                ->description($this->getChangeDescription($todayVolume, $yesterdayVolume))
                ->descriptionIcon($this->getChangeIcon($todayVolume, $yesterdayVolume))
                ->color($this->getChangeColor($todayVolume, $yesterdayVolume))
                ->chart($this->getVolumeChart()),
                
            Stat::make('Transactions', Number::format($todayCount))
                ->description($this->getChangeDescription($todayCount, $yesterdayCount, 'count'))
                ->descriptionIcon($this->getChangeIcon($todayCount, $yesterdayCount))
                ->color($this->getChangeColor($todayCount, $yesterdayCount)),
                
            Stat::make('Net Cash Flow', ($netFlow >= 0 ? '+' : '') . '$' . Number::format($netFlow / 100, 2))
                ->description('Deposits - Withdrawals')
                ->color($netFlow >= 0 ? 'success' : 'danger')
                ->icon($netFlow >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'),
                
            Stat::make('Pending', Number::format($pendingCount))
                ->description($failedCount > 0 ? "{$failedCount} failed this week" : 'All systems operational')
                ->color($pendingCount > 10 ? 'warning' : 'success')
                ->icon($pendingCount > 10 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle'),
        ];
    }
    
    private function getChangeDescription($today, $yesterday, $type = 'amount'): string
    {
        if ($yesterday == 0) {
            return 'No data from yesterday';
        }
        
        $change = (($today - $yesterday) / $yesterday) * 100;
        $formatted = Number::format(abs($change), 1);
        
        return ($change >= 0 ? '+' : '-') . $formatted . '% from yesterday';
    }
    
    private function getChangeIcon($today, $yesterday): string
    {
        if ($today == $yesterday) {
            return 'heroicon-m-minus';
        }
        
        return $today > $yesterday ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }
    
    private function getChangeColor($today, $yesterday): string
    {
        if ($today == $yesterday) {
            return 'gray';
        }
        
        return $today > $yesterday ? 'success' : 'danger';
    }
    
    private function getVolumeChart(): array
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $volume = TransactionReadModel::whereDate('processed_at', today()->subDays($i))
                ->sum('amount');
            $data[] = $volume / 100;
        }
        
        return $data;
    }
}