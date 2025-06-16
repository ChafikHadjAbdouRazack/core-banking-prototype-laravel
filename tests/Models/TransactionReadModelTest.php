<?php

declare(strict_types=1);

use App\Models\TransactionReadModel;
use App\Models\Account;
use App\Domain\Asset\Models\Asset;

beforeEach(function () {
    // Create test accounts
    $this->account = Account::factory()->create();
    $this->relatedAccount = Account::factory()->create();
    
    // Create test assets
    Asset::factory()->create(['code' => 'USD']);
    Asset::factory()->create(['code' => 'EUR']);
});

describe('TransactionReadModel Basic Functionality', function () {
    it('can create transaction read model', function () {
        $transaction = TransactionReadModel::create([
            'uuid' => fake()->uuid(),
            'account_uuid' => $this->account->uuid,
            'type' => TransactionReadModel::TYPE_DEPOSIT,
            'amount' => 10000,
            'asset_code' => 'USD',
            'description' => 'Test deposit',
            'hash' => 'test-hash-123',
            'status' => TransactionReadModel::STATUS_COMPLETED,
        ]);

        expect($transaction)->toBeInstanceOf(TransactionReadModel::class);
        expect($transaction->account_uuid)->toBe($this->account->uuid);
        expect($transaction->amount)->toBe(10000);
        expect($transaction->asset_code)->toBe('USD');
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'uuid',
            'account_uuid',
            'type',
            'amount',
            'asset_code',
            'description',
            'hash',
            'status',
            'related_account_uuid',
            'related_transaction_uuid',
            'exchange_rate',
            'reference_currency',
            'reference_amount',
            'metadata',
        ];

        $model = new TransactionReadModel();
        expect($model->getFillable())->toBe($fillable);
    });

    it('has correct casts', function () {
        $transaction = TransactionReadModel::factory()->create([
            'metadata' => ['key' => 'value'],
        ]);

        expect($transaction->metadata)->toBe(['key' => 'value']);
        expect($transaction->metadata)->toBeArray();
    });
});

describe('Transaction Types and Constants', function () {
    it('has correct transaction type constants', function () {
        expect(TransactionReadModel::TYPE_DEPOSIT)->toBe('deposit');
        expect(TransactionReadModel::TYPE_WITHDRAWAL)->toBe('withdrawal');
        expect(TransactionReadModel::TYPE_TRANSFER_IN)->toBe('transfer_in');
        expect(TransactionReadModel::TYPE_TRANSFER_OUT)->toBe('transfer_out');
    });

    it('has correct status constants', function () {
        expect(TransactionReadModel::STATUS_PENDING)->toBe('pending');
        expect(TransactionReadModel::STATUS_COMPLETED)->toBe('completed');
        expect(TransactionReadModel::STATUS_FAILED)->toBe('failed');
        expect(TransactionReadModel::STATUS_REVERSED)->toBe('reversed');
    });

    it('has correct direction constants', function () {
        expect(TransactionReadModel::DIRECTION_CREDIT)->toBe('credit');
        expect(TransactionReadModel::DIRECTION_DEBIT)->toBe('debit');
    });
});

describe('Helper Methods', function () {
    it('correctly determines credit direction for deposits and transfers in', function () {
        $deposit = TransactionReadModel::factory()->create([
            'type' => TransactionReadModel::TYPE_DEPOSIT,
        ]);

        $transferIn = TransactionReadModel::factory()->create([
            'type' => TransactionReadModel::TYPE_TRANSFER_IN,
        ]);

        expect($deposit->getDirection())->toBe(TransactionReadModel::DIRECTION_CREDIT);
        expect($transferIn->getDirection())->toBe(TransactionReadModel::DIRECTION_CREDIT);
    });

    it('correctly determines debit direction for withdrawals and transfers out', function () {
        $withdrawal = TransactionReadModel::factory()->create([
            'type' => TransactionReadModel::TYPE_WITHDRAWAL,
        ]);

        $transferOut = TransactionReadModel::factory()->create([
            'type' => TransactionReadModel::TYPE_TRANSFER_OUT,
        ]);

        expect($withdrawal->getDirection())->toBe(TransactionReadModel::DIRECTION_DEBIT);
        expect($transferOut->getDirection())->toBe(TransactionReadModel::DIRECTION_DEBIT);
    });

    it('correctly identifies credit transactions', function () {
        $deposit = TransactionReadModel::factory()->create([
            'type' => TransactionReadModel::TYPE_DEPOSIT,
        ]);

        $withdrawal = TransactionReadModel::factory()->create([
            'type' => TransactionReadModel::TYPE_WITHDRAWAL,
        ]);

        expect($deposit->isCredit())->toBeTrue();
        expect($withdrawal->isCredit())->toBeFalse();
    });

    it('correctly identifies debit transactions', function () {
        $deposit = TransactionReadModel::factory()->create([
            'type' => TransactionReadModel::TYPE_DEPOSIT,
        ]);

        $withdrawal = TransactionReadModel::factory()->create([
            'type' => TransactionReadModel::TYPE_WITHDRAWAL,
        ]);

        expect($deposit->isDebit())->toBeFalse();
        expect($withdrawal->isDebit())->toBeTrue();
    });

    it('correctly identifies transfer transactions', function () {
        $deposit = TransactionReadModel::factory()->create([
            'type' => TransactionReadModel::TYPE_DEPOSIT,
        ]);

        $transferIn = TransactionReadModel::factory()->create([
            'type' => TransactionReadModel::TYPE_TRANSFER_IN,
        ]);

        $transferOut = TransactionReadModel::factory()->create([
            'type' => TransactionReadModel::TYPE_TRANSFER_OUT,
        ]);

        expect($deposit->isTransfer())->toBeFalse();
        expect($transferIn->isTransfer())->toBeTrue();
        expect($transferOut->isTransfer())->toBeTrue();
    });

    it('correctly identifies cross-asset transactions', function () {
        $sameAsset = TransactionReadModel::factory()->create([
            'asset_code' => 'USD',
            'reference_currency' => null,
        ]);

        $crossAsset = TransactionReadModel::factory()->create([
            'asset_code' => 'USD',
            'reference_currency' => 'EUR',
            'exchange_rate' => '0.8500000000',
        ]);

        expect($sameAsset->isCrossAsset())->toBeFalse();
        expect($crossAsset->isCrossAsset())->toBeTrue();
    });

    it('formats amount correctly for USD', function () {
        $transaction = TransactionReadModel::factory()->create([
            'amount' => 123456, // $1,234.56
            'asset_code' => 'USD',
        ]);

        expect($transaction->getFormattedAmount())->toBe('$1,234.56');
    });

    it('formats amount correctly for EUR', function () {
        $transaction = TransactionReadModel::factory()->create([
            'amount' => 987654, // €9,876.54
            'asset_code' => 'EUR',
        ]);

        expect($transaction->getFormattedAmount())->toBe('€9,876.54');
    });

    it('formats amount with custom precision for BTC', function () {
        Asset::factory()->create(['code' => 'BTC', 'precision' => 8]);
        
        $transaction = TransactionReadModel::factory()->create([
            'amount' => 100000000, // 1.00000000 BTC
            'asset_code' => 'BTC',
        ]);

        expect($transaction->getFormattedAmount())->toBe('1.00000000 BTC');
    });
});

describe('Relationships', function () {
    it('belongs to account', function () {
        $transaction = TransactionReadModel::factory()->create([
            'account_uuid' => $this->account->uuid,
        ]);

        expect($transaction->account)->toBeInstanceOf(Account::class);
        expect($transaction->account->uuid)->toBe($this->account->uuid);
    });

    it('belongs to asset', function () {
        $transaction = TransactionReadModel::factory()->create([
            'asset_code' => 'USD',
        ]);

        expect($transaction->asset)->toBeInstanceOf(Asset::class);
        expect($transaction->asset->code)->toBe('USD');
    });

    it('can have related account for transfers', function () {
        $transaction = TransactionReadModel::factory()->create([
            'type' => TransactionReadModel::TYPE_TRANSFER_OUT,
            'related_account_uuid' => $this->relatedAccount->uuid,
        ]);

        expect($transaction->relatedAccount)->toBeInstanceOf(Account::class);
        expect($transaction->relatedAccount->uuid)->toBe($this->relatedAccount->uuid);
    });

    it('can have related transaction for bidirectional transfers', function () {
        // Create outgoing transaction
        $outgoingTransaction = TransactionReadModel::factory()->create([
            'account_uuid' => $this->account->uuid,
            'type' => TransactionReadModel::TYPE_TRANSFER_OUT,
            'related_account_uuid' => $this->relatedAccount->uuid,
        ]);

        // Create incoming transaction with relationship
        $incomingTransaction = TransactionReadModel::factory()->create([
            'account_uuid' => $this->relatedAccount->uuid,
            'type' => TransactionReadModel::TYPE_TRANSFER_IN,
            'related_account_uuid' => $this->account->uuid,
            'related_transaction_uuid' => $outgoingTransaction->uuid,
        ]);

        // Update outgoing transaction with bidirectional link
        $outgoingTransaction->update([
            'related_transaction_uuid' => $incomingTransaction->uuid,
        ]);

        expect($outgoingTransaction->fresh()->relatedTransaction)->toBeInstanceOf(TransactionReadModel::class);
        expect($outgoingTransaction->relatedTransaction->uuid)->toBe($incomingTransaction->uuid);
        
        expect($incomingTransaction->relatedTransaction)->toBeInstanceOf(TransactionReadModel::class);
        expect($incomingTransaction->relatedTransaction->uuid)->toBe($outgoingTransaction->uuid);
    });
});

describe('Scopes', function () {
    beforeEach(function () {
        // Create test transactions
        TransactionReadModel::factory()->create([
            'account_uuid' => $this->account->uuid,
            'type' => TransactionReadModel::TYPE_DEPOSIT,
            'asset_code' => 'USD',
        ]);

        TransactionReadModel::factory()->create([
            'account_uuid' => $this->account->uuid,
            'type' => TransactionReadModel::TYPE_WITHDRAWAL,
            'asset_code' => 'USD',
        ]);

        TransactionReadModel::factory()->create([
            'account_uuid' => $this->account->uuid,
            'type' => TransactionReadModel::TYPE_TRANSFER_IN,
            'asset_code' => 'EUR',
        ]);
    });

    it('can filter by account', function () {
        $transactions = TransactionReadModel::forAccount($this->account->uuid)->get();
        
        expect($transactions)->toHaveCount(3);
        $transactions->each(function ($transaction) {
            expect($transaction->account_uuid)->toBe($this->account->uuid);
        });
    });

    it('can filter by asset', function () {
        $usdTransactions = TransactionReadModel::forAsset('USD')->get();
        $eurTransactions = TransactionReadModel::forAsset('EUR')->get();
        
        expect($usdTransactions)->toHaveCount(2);
        expect($eurTransactions)->toHaveCount(1);
    });

    it('can filter by type', function () {
        $deposits = TransactionReadModel::ofType(TransactionReadModel::TYPE_DEPOSIT)->get();
        $withdrawals = TransactionReadModel::ofType(TransactionReadModel::TYPE_WITHDRAWAL)->get();
        
        expect($deposits)->toHaveCount(1);
        expect($withdrawals)->toHaveCount(1);
    });

    it('can filter by status', function () {
        // Update one transaction to be pending
        TransactionReadModel::first()->update(['status' => TransactionReadModel::STATUS_PENDING]);
        
        $completed = TransactionReadModel::withStatus(TransactionReadModel::STATUS_COMPLETED)->get();
        $pending = TransactionReadModel::withStatus(TransactionReadModel::STATUS_PENDING)->get();
        
        expect($completed)->toHaveCount(2);
        expect($pending)->toHaveCount(1);
    });

    it('can filter credit transactions', function () {
        $credits = TransactionReadModel::credits()->get();
        
        expect($credits)->toHaveCount(2); // deposit + transfer_in
        $credits->each(function ($transaction) {
            expect($transaction->isCredit())->toBeTrue();
        });
    });

    it('can filter debit transactions', function () {
        $debits = TransactionReadModel::debits()->get();
        
        expect($debits)->toHaveCount(1); // withdrawal
        $debits->each(function ($transaction) {
            expect($transaction->isDebit())->toBeTrue();
        });
    });

    it('can filter transfers', function () {
        $transfers = TransactionReadModel::transfers()->get();
        
        expect($transfers)->toHaveCount(1); // transfer_in
        $transfers->each(function ($transaction) {
            expect($transaction->isTransfer())->toBeTrue();
        });
    });
});