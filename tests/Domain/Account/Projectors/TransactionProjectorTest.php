<?php

declare(strict_types=1);

use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use App\Domain\Account\Events\MoneyTransferred;
use App\Domain\Account\Events\AssetBalanceAdded;
use App\Domain\Account\Events\AssetBalanceSubtracted;
use App\Domain\Account\Events\AssetTransferred;
use App\Domain\Account\Projectors\TransactionProjector;
use App\Models\Account;
use App\Models\TransactionReadModel;
use App\Domain\Asset\Models\Asset;
use App\Models\AccountBalance;

beforeEach(function () {
    $this->projector = app(TransactionProjector::class);
    
    // Create test accounts
    $this->fromAccount = Account::factory()->create();
    $this->toAccount = Account::factory()->create();
    
    // Create USD asset
    Asset::factory()->create(['code' => 'USD']);
    Asset::factory()->create(['code' => 'EUR']);
    
    // Create USD balances for accounts
    AccountBalance::factory()->create([
        'account_uuid' => $this->fromAccount->uuid,
        'asset_code' => 'USD',
        'balance' => 100000, // $1000.00
    ]);
    
    AccountBalance::factory()->create([
        'account_uuid' => $this->toAccount->uuid,
        'asset_code' => 'USD',
        'balance' => 50000, // $500.00
    ]);
});

describe('Legacy USD Events', function () {
    it('creates transaction record on money added', function () {
        $accountUuid = AccountUuid::fromString($this->fromAccount->uuid);
        $money = new Money(10000); // $100.00
        $hash = Hash::make('test-data');
        
        $event = new MoneyAdded($money, $hash);
        $event->aggregateRootUuid = $accountUuid->toString();
        
        $this->projector->onMoneyAdded($event);
        
        $transaction = TransactionReadModel::where('account_uuid', $accountUuid->toString())->first();
        
        expect($transaction)->not->toBeNull();
        expect($transaction->type)->toBe(TransactionReadModel::TYPE_DEPOSIT);
        expect($transaction->amount)->toBe(10000);
        expect($transaction->asset_code)->toBe('USD');
        expect($transaction->hash)->toBe($hash->toString());
        expect($transaction->status)->toBe(TransactionReadModel::STATUS_COMPLETED);
    });

    it('creates transaction record on money subtracted', function () {
        $accountUuid = AccountUuid::fromString($this->fromAccount->uuid);
        $money = new Money(5000); // $50.00
        $hash = Hash::make('test-withdrawal');
        
        $event = new MoneySubtracted($money, $hash);
        $event->aggregateRootUuid = $accountUuid->toString();
        
        $this->projector->onMoneySubtracted($event);
        
        $transaction = TransactionReadModel::where('account_uuid', $accountUuid->toString())->first();
        
        expect($transaction)->not->toBeNull();
        expect($transaction->type)->toBe(TransactionReadModel::TYPE_WITHDRAWAL);
        expect($transaction->amount)->toBe(5000);
        expect($transaction->asset_code)->toBe('USD');
        expect($transaction->hash)->toBe($hash->toString());
    });

    it('creates bidirectional transaction records on money transferred', function () {
        $fromUuid = AccountUuid::fromString($this->fromAccount->uuid);
        $toUuid = AccountUuid::fromString($this->toAccount->uuid);
        $money = new Money(7500); // $75.00
        $hash = Hash::make('test-transfer');
        
        $event = new MoneyTransferred($fromUuid, $toUuid, $money, $hash);
        $event->aggregateRootUuid = $fromUuid->toString();
        
        $this->projector->onMoneyTransferred($event);
        
        // Check outgoing transaction
        $outgoingTransaction = TransactionReadModel::where('account_uuid', $fromUuid->toString())
            ->where('type', TransactionReadModel::TYPE_TRANSFER_OUT)
            ->first();
        
        expect($outgoingTransaction)->not->toBeNull();
        expect($outgoingTransaction->amount)->toBe(7500);
        expect($outgoingTransaction->related_account_uuid)->toBe($toUuid->toString());
        
        // Check incoming transaction
        $incomingTransaction = TransactionReadModel::where('account_uuid', $toUuid->toString())
            ->where('type', TransactionReadModel::TYPE_TRANSFER_IN)
            ->first();
        
        expect($incomingTransaction)->not->toBeNull();
        expect($incomingTransaction->amount)->toBe(7500);
        expect($incomingTransaction->related_account_uuid)->toBe($fromUuid->toString());
        
        // Check bidirectional linking
        expect($outgoingTransaction->related_transaction_uuid)->toBe($incomingTransaction->uuid);
        expect($incomingTransaction->related_transaction_uuid)->toBe($outgoingTransaction->uuid);
    });
});

describe('Multi-Asset Events', function () {
    it('creates transaction record on asset balance added', function () {
        $accountUuid = AccountUuid::fromString($this->fromAccount->uuid);
        $hash = Hash::make('test-asset-deposit');
        
        $event = new AssetBalanceAdded('EUR', 25000, $hash); // €250.00
        $event->aggregateRootUuid = $accountUuid->toString();
        
        $this->projector->onAssetBalanceAdded($event);
        
        $transaction = TransactionReadModel::where('account_uuid', $accountUuid->toString())
            ->where('asset_code', 'EUR')
            ->first();
        
        expect($transaction)->not->toBeNull();
        expect($transaction->type)->toBe(TransactionReadModel::TYPE_DEPOSIT);
        expect($transaction->amount)->toBe(25000);
        expect($transaction->asset_code)->toBe('EUR');
        expect($transaction->hash)->toBe($hash->toString());
    });

    it('creates transaction record on asset balance subtracted', function () {
        $accountUuid = AccountUuid::fromString($this->fromAccount->uuid);
        $hash = Hash::make('test-asset-withdrawal');
        
        $event = new AssetBalanceSubtracted('EUR', 15000, $hash); // €150.00
        $event->aggregateRootUuid = $accountUuid->toString();
        
        $this->projector->onAssetBalanceSubtracted($event);
        
        $transaction = TransactionReadModel::where('account_uuid', $accountUuid->toString())
            ->where('asset_code', 'EUR')
            ->first();
        
        expect($transaction)->not->toBeNull();
        expect($transaction->type)->toBe(TransactionReadModel::TYPE_WITHDRAWAL);
        expect($transaction->amount)->toBe(15000);
        expect($transaction->asset_code)->toBe('EUR');
    });

    it('creates cross-asset transfer records with exchange rate', function () {
        $fromUuid = AccountUuid::fromString($this->fromAccount->uuid);
        $toUuid = AccountUuid::fromString($this->toAccount->uuid);
        $hash = Hash::make('test-cross-asset-transfer');
        
        $event = new AssetTransferred(
            $fromUuid,
            $toUuid,
            'USD',
            10000, // $100.00
            'EUR',
            8500,  // €85.00
            0.85,  // Exchange rate
            $hash
        );
        $event->aggregateRootUuid = $fromUuid->toString();
        
        $this->projector->onAssetTransferred($event);
        
        // Check outgoing USD transaction
        $outgoingTransaction = TransactionReadModel::where('account_uuid', $fromUuid->toString())
            ->where('asset_code', 'USD')
            ->where('type', TransactionReadModel::TYPE_TRANSFER_OUT)
            ->first();
        
        expect($outgoingTransaction)->not->toBeNull();
        expect($outgoingTransaction->amount)->toBe(10000);
        expect($outgoingTransaction->exchange_rate)->toBe('0.8500000000');
        expect($outgoingTransaction->reference_currency)->toBe('EUR');
        expect($outgoingTransaction->reference_amount)->toBe(8500);
        
        // Check incoming EUR transaction
        $incomingTransaction = TransactionReadModel::where('account_uuid', $toUuid->toString())
            ->where('asset_code', 'EUR')
            ->where('type', TransactionReadModel::TYPE_TRANSFER_IN)
            ->first();
        
        expect($incomingTransaction)->not->toBeNull();
        expect($incomingTransaction->amount)->toBe(8500);
        expect($incomingTransaction->exchange_rate)->toBe('0.8500000000');
        expect($incomingTransaction->reference_currency)->toBe('USD');
        expect($incomingTransaction->reference_amount)->toBe(10000);
        
        // Check bidirectional linking
        expect($outgoingTransaction->related_transaction_uuid)->toBe($incomingTransaction->uuid);
        expect($incomingTransaction->related_transaction_uuid)->toBe($outgoingTransaction->uuid);
    });

    it('creates same-asset transfer records without exchange rate', function () {
        $fromUuid = AccountUuid::fromString($this->fromAccount->uuid);
        $toUuid = AccountUuid::fromString($this->toAccount->uuid);
        $hash = Hash::make('test-same-asset-transfer');
        
        $event = new AssetTransferred(
            $fromUuid,
            $toUuid,
            'USD',
            12000, // $120.00
            'USD',
            12000, // $120.00
            1.0,   // Identity rate
            $hash
        );
        $event->aggregateRootUuid = $fromUuid->toString();
        
        $this->projector->onAssetTransferred($event);
        
        // Check outgoing transaction
        $outgoingTransaction = TransactionReadModel::where('account_uuid', $fromUuid->toString())
            ->where('type', TransactionReadModel::TYPE_TRANSFER_OUT)
            ->first();
        
        expect($outgoingTransaction)->not->toBeNull();
        expect($outgoingTransaction->amount)->toBe(12000);
        expect($outgoingTransaction->asset_code)->toBe('USD');
        expect($outgoingTransaction->exchange_rate)->toBeNull(); // No exchange rate for same asset
        expect($outgoingTransaction->reference_currency)->toBeNull();
        expect($outgoingTransaction->reference_amount)->toBeNull();
        
        // Check incoming transaction
        $incomingTransaction = TransactionReadModel::where('account_uuid', $toUuid->toString())
            ->where('type', TransactionReadModel::TYPE_TRANSFER_IN)
            ->first();
        
        expect($incomingTransaction)->not->toBeNull();
        expect($incomingTransaction->amount)->toBe(12000);
        expect($incomingTransaction->asset_code)->toBe('USD');
        expect($incomingTransaction->exchange_rate)->toBeNull();
    });
});