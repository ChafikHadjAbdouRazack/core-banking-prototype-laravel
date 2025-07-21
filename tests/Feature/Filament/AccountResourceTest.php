<?php

declare(strict_types=1);

use App\Domain\Account\Models\AccountBalance;
use App\Filament\Admin\Resources\AccountResource;
use App\Models\Account;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->setUpFilamentWithAuth();
});

describe('Filament Admin Dashboard', function () {

    it('can render account index page', function () {
        get('/admin/accounts')
            ->assertSuccessful();
    });

    it('can list accounts', function () {
        $accounts = Account::factory()->count(5)->create();

        livewire(AccountResource\Pages\ListAccounts::class)
            ->assertCanSeeTableRecords($accounts);
    });

    it('can render create account page', function () {
        get('/admin/accounts/create')
            ->assertSuccessful();
    });

    it('can create an account', function () {
        $user = User::factory()->create();

        $newData = [
            'name'      => 'Test Savings Account',
            'user_uuid' => $user->uuid,
        ];

        livewire(AccountResource\Pages\CreateAccount::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        assertDatabaseHas('accounts', [
            'name' => 'Test Savings Account',
            'user_uuid' => $user->uuid,
        ]);
    });

    it('can render account edit page', function () {
        $account = Account::factory()->create();

        get("/admin/accounts/{$account->id}/edit")
            ->assertSuccessful();
    });

    it('can update account', function () {
        $account = Account::factory()->create();
        $newData = [
            'name'   => 'Updated Account Name',
            'frozen' => true,
        ];

        livewire(AccountResource\Pages\EditAccount::class, [
            'record' => $account->id,
        ])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas('accounts', [
            'uuid'   => $account->uuid,
            'name'   => 'Updated Account Name',
            'frozen' => true,
        ]);
    });

    it('can deposit money to account', function () {
        $account = Account::factory()->create();
        // Create initial balance
        AccountBalance::factory()->create([
            'account_uuid' => $account->uuid,
            'asset_code' => 'USD',
            'balance' => 1000,
        ]);

        livewire(AccountResource\Pages\ListAccounts::class)
            ->callTableAction('deposit', $account, data: [
                'amount' => 50.00,
            ])
            ->assertHasNoTableActionErrors();

        $account->refresh();
        expect($account->balance)->toBe(6000); // 1000 + 5000 (50.00 * 100)
    });

    it('can withdraw money from account', function () {
        $account = Account::factory()->create();
        // Create initial balance
        AccountBalance::factory()->create([
            'account_uuid' => $account->uuid,
            'asset_code' => 'USD',
            'balance' => 10000,
        ]);

        livewire(AccountResource\Pages\ListAccounts::class)
            ->callTableAction('withdraw', $account, data: [
                'amount' => 25.00,
            ])
            ->assertHasNoTableActionErrors();

        $account->refresh();
        expect($account->balance)->toBe(7500); // 10000 - 2500 (25.00 * 100)
    });

    it('can freeze an account', function () {
        $account = Account::factory()->create(['frozen' => false]);

        livewire(AccountResource\Pages\ListAccounts::class)
            ->callTableAction('freeze', $account)
            ->assertHasNoTableActionErrors();

        $account->refresh();
        expect($account->frozen)->toBeTrue();
    });

    it('can unfreeze an account', function () {
        $account = Account::factory()->create(['frozen' => true]);

        livewire(AccountResource\Pages\ListAccounts::class)
            ->callTableAction('unfreeze', $account)
            ->assertHasNoTableActionErrors();

        $account->refresh();
        expect($account->frozen)->toBeFalse();
    });

    it('cannot deposit to frozen account', function () {
        $account = Account::factory()->create(['frozen' => true]);

        livewire(AccountResource\Pages\ListAccounts::class)
            ->assertTableActionHidden('deposit', $account);
    });

    it('cannot withdraw from frozen account', function () {
        $account = Account::factory()->create(['frozen' => true]);

        livewire(AccountResource\Pages\ListAccounts::class)
            ->assertTableActionHidden('withdraw', $account);
    });

    it('can filter accounts by status', function () {
        $activeAccounts = Account::factory()->count(3)->create(['frozen' => false]);
        $frozenAccounts = Account::factory()->count(2)->create(['frozen' => true]);

        livewire(AccountResource\Pages\ListAccounts::class)
            ->assertCanSeeTableRecords([...$activeAccounts, ...$frozenAccounts])
            ->filterTable('frozen', '1')
            ->assertCanSeeTableRecords($frozenAccounts)
            ->assertCanNotSeeTableRecords($activeAccounts);
    });

    it('can filter accounts by balance', function () {
        $poorAccounts = Account::factory()->count(2)->create();
        $richAccounts = Account::factory()->count(3)->create();

        // Create balances for poor accounts
        foreach ($poorAccounts as $account) {
            AccountBalance::factory()->create([
                'account_uuid' => $account->uuid,
                'asset_code' => 'USD',
                'balance' => 100,
            ]);
        }

        // Create balances for rich accounts
        foreach ($richAccounts as $account) {
            AccountBalance::factory()->create([
                'account_uuid' => $account->uuid,
                'asset_code' => 'USD',
                'balance' => 100000,
            ]);
        }

        livewire(AccountResource\Pages\ListAccounts::class)
            ->assertCanSeeTableRecords([...$poorAccounts, ...$richAccounts])
            ->filterTable('balance', ['balance_operator' => '>', 'balance_amount' => 500])
            ->assertCanSeeTableRecords($richAccounts)
            ->assertCanNotSeeTableRecords($poorAccounts);
    });

    it('can search accounts by name', function () {
        $account1 = Account::factory()->create(['name' => 'John Doe Savings']);
        $account2 = Account::factory()->create(['name' => 'Jane Smith Checking']);

        livewire(AccountResource\Pages\ListAccounts::class)
            ->searchTable('John')
            ->assertCanSeeTableRecords([$account1])
            ->assertCanNotSeeTableRecords([$account2]);
    });

    it('can view account details', function () {
        $account = Account::factory()->create();

        get("/admin/accounts/{$account->id}")
            ->assertSuccessful()
            ->assertSee($account->name)
            ->assertSee($account->uuid);
    });

    it('shows account statistics widget', function () {
        $accounts = Account::factory()->count(5)->create([
            'frozen'  => false,
        ]);

        // Create balances for active accounts
        foreach ($accounts as $account) {
            AccountBalance::factory()->create([
                'account_uuid' => $account->uuid,
                'asset_code' => 'USD',
                'balance' => 10000,
            ]);
        }

        $frozenAccounts = Account::factory()->count(2)->create([
            'frozen'  => true,
        ]);

        // Create balances for frozen accounts
        foreach ($frozenAccounts as $account) {
            AccountBalance::factory()->create([
                'account_uuid' => $account->uuid,
                'asset_code' => 'USD',
                'balance' => 5000,
            ]);
        }

        livewire(AccountResource\Widgets\AccountStatsOverview::class)
            ->assertSee('Total Accounts')
            ->assertSee('7') // Total accounts
            ->assertSee('5 active, 2 frozen')
            ->assertSee('Total Balance')
            ->assertSee('$600.00'); // (5 * 100) + (2 * 50) = 600
    });
});
