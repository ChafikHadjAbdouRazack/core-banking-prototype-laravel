<?php

namespace App\Filament\Exports;

use App\Models\Transaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TransactionExporter extends Exporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('uuid')
                ->label('Transaction ID'),
            ExportColumn::make('account.name')
                ->label('Account Name'),
            ExportColumn::make('type')
                ->label('Type')
                ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
            ExportColumn::make('amount')
                ->label('Amount (USD)')
                ->formatStateUsing(fn ($state) => '$' . number_format($state / 100, 2)),
            ExportColumn::make('balance_after')
                ->label('Balance After (USD)')
                ->formatStateUsing(fn ($state) => '$' . number_format($state / 100, 2)),
            ExportColumn::make('reference')
                ->label('Reference'),
            ExportColumn::make('hash')
                ->label('Security Hash'),
            ExportColumn::make('created_at')
                ->label('Transaction Date'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your transaction export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}