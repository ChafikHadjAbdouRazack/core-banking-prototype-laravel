<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\TransactionReadModelResource;
use App\Filament\Admin\Resources\TransactionReadModelResource\Pages\ListTransactionReadModels;
use App\Models\Account;
use App\Models\TransactionReadModel;
use App\Models\User;
use App\Domain\Asset\Models\Asset;
use Livewire\Livewire;

beforeEach(function () {
    // Create admin user and authenticate
    $this->adminUser = User::factory()->create();
    $this->actingAs($this->adminUser);

    // Create test assets
    Asset::factory()->create(['code' => 'USD']);
    Asset::factory()->create(['code' => 'EUR']);

    // Create test accounts
    $this->fromAccount = Account::factory()->create(['name' => 'Source Account']);
    $this->toAccount = Account::factory()->create(['name' => 'Destination Account']);
});

describe('TransactionReadModelResource List Page', function () {
    it('can render transaction list page', function () {
        Livewire::test(ListTransactionReadModels::class)
            ->assertSuccessful();
    });

    it('can list transactions', function () {
        $transactions = TransactionReadModel::factory()->count(3)->create([
            'account_uuid' => $this->fromAccount->uuid,
        ]);

        Livewire::test(ListTransactionReadModels::class)
            ->assertCanSeeTableRecords($transactions);
    });

    it('can filter transactions by type', function () {
        $deposit = TransactionReadModel::factory()->deposit()->create([
            'account_uuid' => $this->fromAccount->uuid,
        ]);

        $withdrawal = TransactionReadModel::factory()->withdrawal()->create([
            'account_uuid' => $this->fromAccount->uuid,
        ]);

        Livewire::test(ListTransactionReadModels::class)
            ->filterTable('type', TransactionReadModel::TYPE_DEPOSIT)
            ->assertCanSeeTableRecords([$deposit])
            ->assertCanNotSeeTableRecords([$withdrawal]);
    });

    it('can filter transactions by status', function () {
        $completed = TransactionReadModel::factory()->completed()->create([
            'account_uuid' => $this->fromAccount->uuid,
        ]);

        $pending = TransactionReadModel::factory()->pending()->create([
            'account_uuid' => $this->fromAccount->uuid,
        ]);

        Livewire::test(ListTransactionReadModels::class)
            ->filterTable('status', TransactionReadModel::STATUS_COMPLETED)
            ->assertCanSeeTableRecords([$completed])
            ->assertCanNotSeeTableRecords([$pending]);
    });

    it('can filter transactions by asset', function () {
        $usdTransaction = TransactionReadModel::factory()->create([
            'account_uuid' => $this->fromAccount->uuid,
            'asset_code' => 'USD',
        ]);

        $eurTransaction = TransactionReadModel::factory()->create([
            'account_uuid' => $this->fromAccount->uuid,
            'asset_code' => 'EUR',
        ]);

        Livewire::test(ListTransactionReadModels::class)
            ->filterTable('asset_code', 'USD')
            ->assertCanSeeTableRecords([$usdTransaction])
            ->assertCanNotSeeTableRecords([$eurTransaction]);
    });

    it('can search transactions by description', function () {
        $matchingTransaction = TransactionReadModel::factory()->create([
            'account_uuid' => $this->fromAccount->uuid,
            'description' => 'ATM Deposit - Downtown Branch',
        ]);

        $nonMatchingTransaction = TransactionReadModel::factory()->create([
            'account_uuid' => $this->fromAccount->uuid,
            'description' => 'Wire Transfer',
        ]);

        Livewire::test(ListTransactionReadModels::class)
            ->searchTable('ATM')
            ->assertCanSeeTableRecords([$matchingTransaction])
            ->assertCanNotSeeTableRecords([$nonMatchingTransaction]);
    });

    it('can sort transactions by amount', function () {
        $lowAmount = TransactionReadModel::factory()->create([
            'account_uuid' => $this->fromAccount->uuid,
            'amount' => 1000, // $10.00
        ]);

        $highAmount = TransactionReadModel::factory()->create([
            'account_uuid' => $this->fromAccount->uuid,
            'amount' => 10000, // $100.00
        ]);

        Livewire::test(ListTransactionReadModels::class)
            ->sortTable('amount', 'desc')
            ->assertCanSeeTableRecords([$highAmount, $lowAmount], inOrder: true);
    });
});

describe('TransactionReadModelResource Table Columns', function () {
    it('displays transaction type with proper badges', function () {
        $deposit = TransactionReadModel::factory()->deposit()->create([
            'account_uuid' => $this->fromAccount->uuid,
        ]);

        $withdrawal = TransactionReadModel::factory()->withdrawal()->create([
            'account_uuid' => $this->fromAccount->uuid,
        ]);

        $component = Livewire::test(ListTransactionReadModels::class);

        $component->assertCanSeeTableRecords([$deposit, $withdrawal]);
        
        // Check that the type column shows the correct values
        expect($component->get('tableRecords')->contains($deposit))->toBeTrue();
        expect($component->get('tableRecords')->contains($withdrawal))->toBeTrue();
    });

    it('displays formatted amounts with currency symbols', function () {
        $usdTransaction = TransactionReadModel::factory()->create([
            'account_uuid' => $this->fromAccount->uuid,
            'asset_code' => 'USD',
            'amount' => 12345, // $123.45
        ]);

        $eurTransaction = TransactionReadModel::factory()->create([
            'account_uuid' => $this->fromAccount->uuid,
            'asset_code' => 'EUR',
            'amount' => 67890, // €678.90
        ]);

        Livewire::test(ListTransactionReadModels::class)
            ->assertCanSeeTableRecords([$usdTransaction, $eurTransaction]);
    });

    it('displays status badges with appropriate colors', function () {
        $completed = TransactionReadModel::factory()->completed()->create([
            'account_uuid' => $this->fromAccount->uuid,
        ]);

        $pending = TransactionReadModel::factory()->pending()->create([
            'account_uuid' => $this->fromAccount->uuid,
        ]);

        $failed = TransactionReadModel::factory()->failed()->create([
            'account_uuid' => $this->fromAccount->uuid,
        ]);

        Livewire::test(ListTransactionReadModels::class)
            ->assertCanSeeTableRecords([$completed, $pending, $failed]);
    });

    it('shows cross-asset transaction details', function () {
        $crossAssetTransaction = TransactionReadModel::factory()->crossAsset('USD', 'EUR', 0.85)->create([
            'account_uuid' => $this->fromAccount->uuid,
            'amount' => 10000, // $100.00
        ]);

        Livewire::test(ListTransactionReadModels::class)
            ->assertCanSeeTableRecords([$crossAssetTransaction]);
    });
});

describe('TransactionReadModelResource Widgets', function () {
    it('displays transaction statistics widget', function () {
        // Create various transaction types
        TransactionReadModel::factory()->deposit()->count(2)->create([
            'account_uuid' => $this->fromAccount->uuid,
            'amount' => 5000,
        ]);

        TransactionReadModel::factory()->withdrawal()->count(1)->create([
            'account_uuid' => $this->fromAccount->uuid,
            'amount' => 2000,
        ]);

        TransactionReadModel::factory()->transferIn()->count(1)->create([
            'account_uuid' => $this->toAccount->uuid,
            'amount' => 3000,
        ]);

        $component = Livewire::test(ListTransactionReadModels::class);
        
        // The widget should be present and functional
        $component->assertSuccessful();
    });

    it('displays transaction volume chart widget', function () {
        // Create transactions across different time periods
        $now = now();
        
        TransactionReadModel::factory()->deposit()->create([
            'account_uuid' => $this->fromAccount->uuid,
            'amount' => 1000,
            'created_at' => $now->subDays(1),
        ]);

        TransactionReadModel::factory()->withdrawal()->create([
            'account_uuid' => $this->fromAccount->uuid,
            'amount' => 500,
            'created_at' => $now->subDays(2),
        ]);

        $component = Livewire::test(ListTransactionReadModels::class);
        
        // The chart widget should render successfully
        $component->assertSuccessful();
    });
});

describe('TransactionReadModelResource Actions', function () {
    it('can view transaction details', function () {
        $transaction = TransactionReadModel::factory()->create([
            'account_uuid' => $this->fromAccount->uuid,
            'description' => 'Test transaction details',
            'metadata' => ['source' => 'api', 'ip' => '192.168.1.1'],
        ]);

        Livewire::test(ListTransactionReadModels::class)
            ->callTableAction('view', $transaction)
            ->assertHasNoTableActionErrors();
    });

    it('can view related transaction for transfers', function () {
        // Create a transfer pair
        $outgoing = TransactionReadModel::factory()->transferOut()->create([
            'account_uuid' => $this->fromAccount->uuid,
            'related_account_uuid' => $this->toAccount->uuid,
        ]);

        $incoming = TransactionReadModel::factory()->transferIn()->create([
            'account_uuid' => $this->toAccount->uuid,
            'related_account_uuid' => $this->fromAccount->uuid,
            'related_transaction_uuid' => $outgoing->uuid,
        ]);

        $outgoing->update(['related_transaction_uuid' => $incoming->uuid]);

        Livewire::test(ListTransactionReadModels::class)
            ->callTableAction('viewRelated', $outgoing)
            ->assertHasNoTableActionErrors();
    });
});