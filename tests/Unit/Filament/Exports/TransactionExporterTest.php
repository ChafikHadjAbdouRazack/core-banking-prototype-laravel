<?php

use App\Filament\Exports\TransactionExporter;
use App\Models\Transaction;
use Filament\Actions\Exports\Models\Export;

it('can define export columns for transactions', function () {
    $columns = TransactionExporter::getColumns();
    
    expect($columns)->toHaveCount(8)
        ->and($columns[0]->getName())->toBe('uuid')
        ->and($columns[0]->getLabel())->toBe('Transaction ID')
        ->and($columns[1]->getName())->toBe('account.name')
        ->and($columns[1]->getLabel())->toBe('Account Name')
        ->and($columns[2]->getName())->toBe('type')
        ->and($columns[2]->getLabel())->toBe('Type')
        ->and($columns[3]->getName())->toBe('amount')
        ->and($columns[3]->getLabel())->toBe('Amount (USD)')
        ->and($columns[4]->getName())->toBe('balance_after')
        ->and($columns[4]->getLabel())->toBe('Balance After (USD)')
        ->and($columns[5]->getName())->toBe('reference')
        ->and($columns[5]->getLabel())->toBe('Reference')
        ->and($columns[6]->getName())->toBe('hash')
        ->and($columns[6]->getLabel())->toBe('Security Hash')
        ->and($columns[7]->getName())->toBe('created_at')
        ->and($columns[7]->getLabel())->toBe('Transaction Date');
});

it('formats transaction type correctly', function () {
    $columns = TransactionExporter::getColumns();
    $typeColumn = $columns[2];
    
    // Test the format state using method
    $reflection = new ReflectionClass($typeColumn);
    $property = $reflection->getProperty('formatStateUsing');
    $property->setAccessible(true);
    $formatState = $property->getValue($typeColumn);
    
    expect($formatState('deposit'))->toBe('Deposit')
        ->and($formatState('withdrawal'))->toBe('Withdrawal')
        ->and($formatState('transfer_in'))->toBe('Transfer In')
        ->and($formatState('transfer_out'))->toBe('Transfer Out');
});

it('formats amount correctly with dollar sign', function () {
    $columns = TransactionExporter::getColumns();
    $amountColumn = $columns[3];
    
    // Test the format state using method
    $reflection = new ReflectionClass($amountColumn);
    $property = $reflection->getProperty('formatStateUsing');
    $property->setAccessible(true);
    $formatState = $property->getValue($amountColumn);
    
    expect($formatState(10050))->toBe('$100.50')
        ->and($formatState(0))->toBe('$0.00')
        ->and($formatState(999))->toBe('$9.99');
});

it('formats balance after correctly with dollar sign', function () {
    $columns = TransactionExporter::getColumns();
    $balanceColumn = $columns[4];
    
    // Test the format state using method
    $reflection = new ReflectionClass($balanceColumn);
    $property = $reflection->getProperty('formatStateUsing');
    $property->setAccessible(true);
    $formatState = $property->getValue($balanceColumn);
    
    expect($formatState(50000))->toBe('$500.00')
        ->and($formatState(0))->toBe('$0.00');
});

it('generates correct completion notification body', function () {
    $export = new Export();
    $export->successful_rows = 500;
    $export->total_rows = 500;
    
    $body = TransactionExporter::getCompletedNotificationBody($export);
    
    expect($body)->toBe('Your transaction export has completed and 500 rows exported.');
});

it('has correct model association', function () {
    expect(TransactionExporter::getModel())->toBe(Transaction::class);
});