<?php

namespace App\Filament\Admin\Resources\TransactionReadModelResource\Pages;

use App\Filament\Admin\Resources\TransactionReadModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransactionReadModel extends EditRecord
{
    protected static string $resource = TransactionReadModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
