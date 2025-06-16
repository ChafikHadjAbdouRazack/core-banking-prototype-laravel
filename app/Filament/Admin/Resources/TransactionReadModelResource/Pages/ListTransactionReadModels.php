<?php

namespace App\Filament\Admin\Resources\TransactionReadModelResource\Pages;

use App\Filament\Admin\Resources\TransactionReadModelResource;
use App\Filament\Admin\Resources\TransactionReadModelResource\Widgets\TransactionStatsWidget;
use App\Filament\Admin\Resources\TransactionReadModelResource\Widgets\TransactionTypeChartWidget;
use App\Filament\Exports\TransactionExporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransactionReadModels extends ListRecords
{
    protected static string $resource = TransactionReadModelResource::class;
    
    protected static ?string $title = 'Transaction History';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(TransactionExporter::class)
                ->label('Export Transactions')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            TransactionStatsWidget::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            TransactionTypeChartWidget::class,
        ];
    }
}
