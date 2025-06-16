<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TransactionReadModelResource\Pages;
use App\Filament\Admin\Resources\TransactionReadModelResource\RelationManagers;
use App\Models\TransactionReadModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class TransactionReadModelResource extends Resource
{
    protected static ?string $model = TransactionReadModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    
    protected static ?string $navigationLabel = 'Transactions';
    
    protected static ?string $modelLabel = 'Transaction';
    
    protected static ?string $pluralModelLabel = 'Transactions';
    
    protected static ?string $navigationGroup = 'Banking';
    
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('Transaction ID')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('account_uuid')
                            ->label('Account')
                            ->relationship('account', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                TransactionReadModel::TYPE_DEPOSIT => 'Deposit',
                                TransactionReadModel::TYPE_WITHDRAWAL => 'Withdrawal',
                                TransactionReadModel::TYPE_TRANSFER_IN => 'Transfer In',
                                TransactionReadModel::TYPE_TRANSFER_OUT => 'Transfer Out',
                            ])
                            ->disabled(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix(fn ($get) => $get('asset_code') ?: 'USD')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => number_format($state / 100, 2)),
                        Forms\Components\Select::make('asset_code')
                            ->label('Asset')
                            ->relationship('asset', 'name', fn ($query) => $query->where('is_active', true))
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                TransactionReadModel::STATUS_COMPLETED => 'Completed',
                                TransactionReadModel::STATUS_PENDING => 'Pending',
                                TransactionReadModel::STATUS_FAILED => 'Failed',
                                TransactionReadModel::STATUS_REVERSED => 'Reversed',
                            ])
                            ->disabled(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->disabled(),
                        Forms\Components\TextInput::make('related_transaction_uuid')
                            ->label('Related Transaction')
                            ->disabled(),
                        Forms\Components\Select::make('initiated_by')
                            ->label('Initiated By')
                            ->relationship('initiator', 'name')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label('Processed At')
                            ->disabled(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Technical Details')
                    ->schema([
                        Forms\Components\TextInput::make('hash')
                            ->label('Transaction Hash')
                            ->disabled(),
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->disabled(),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Account')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        TransactionReadModel::TYPE_DEPOSIT => 'success',
                        TransactionReadModel::TYPE_WITHDRAWAL => 'danger',
                        TransactionReadModel::TYPE_TRANSFER_IN => 'info',
                        TransactionReadModel::TYPE_TRANSFER_OUT => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        TransactionReadModel::TYPE_DEPOSIT => 'Deposit',
                        TransactionReadModel::TYPE_WITHDRAWAL => 'Withdrawal',
                        TransactionReadModel::TYPE_TRANSFER_IN => 'Transfer In',
                        TransactionReadModel::TYPE_TRANSFER_OUT => 'Transfer Out',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $formatted = number_format($state / 100, 2);
                        $sign = $record->getDirection() === 'credit' ? '+' : '-';
                        return new HtmlString(
                            '<span class="' . ($record->getDirection() === 'credit' ? 'text-success-600' : 'text-danger-600') . '">' .
                            $sign . $formatted . ' ' . $record->asset_code .
                            '</span>'
                        );
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        TransactionReadModel::STATUS_COMPLETED => 'success',
                        TransactionReadModel::STATUS_PENDING => 'warning',
                        TransactionReadModel::STATUS_FAILED => 'danger',
                        TransactionReadModel::STATUS_REVERSED => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('initiator.name')
                    ->label('Initiated By')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('Transaction ID')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Transaction ID copied')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('processed_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        TransactionReadModel::TYPE_DEPOSIT => 'Deposit',
                        TransactionReadModel::TYPE_WITHDRAWAL => 'Withdrawal',
                        TransactionReadModel::TYPE_TRANSFER_IN => 'Transfer In',
                        TransactionReadModel::TYPE_TRANSFER_OUT => 'Transfer Out',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        TransactionReadModel::STATUS_COMPLETED => 'Completed',
                        TransactionReadModel::STATUS_PENDING => 'Pending',
                        TransactionReadModel::STATUS_FAILED => 'Failed',
                        TransactionReadModel::STATUS_REVERSED => 'Reversed',
                    ])
                    ->default(TransactionReadModel::STATUS_COMPLETED),
                Tables\Filters\SelectFilter::make('asset_code')
                    ->label('Asset')
                    ->relationship('asset', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('processed_at', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('processed_at', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('min')
                            ->label('Min Amount')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('max')
                            ->label('Max Amount')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '>=', $amount * 100),
                            )
                            ->when(
                                $data['max'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '<=', $amount * 100),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn (Model $record): string => "Transaction {$record->uuid}")
                    ->modalWidth('lg'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Selected'),
                ]),
            ])
            ->emptyStateHeading('No transactions found')
            ->emptyStateDescription('Transactions will appear here once they are processed.')
            ->emptyStateIcon('heroicon-o-arrow-trending-up');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactionReadModels::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canEdit(Model $record): bool
    {
        return false;
    }
    
    public static function canDelete(Model $record): bool
    {
        return false;
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
