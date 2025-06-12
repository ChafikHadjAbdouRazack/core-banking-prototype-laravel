<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('can freeze an account', function () {
    $account = Account::factory()->create(['frozen' => false]);

    $response = $this->postJson("/api/accounts/{$account->uuid}/freeze", [
        'reason' => 'Suspicious activity detected',
        'authorized_by' => 'admin@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Account frozen successfully',
        ]);
});

it('cannot freeze an already frozen account', function () {
    $account = Account::factory()->create(['frozen' => true]);

    $response = $this->postJson("/api/accounts/{$account->uuid}/freeze", [
        'reason' => 'Duplicate freeze attempt',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Account is already frozen',
            'error' => 'ACCOUNT_ALREADY_FROZEN',
        ]);

    // Workflow should not be dispatched when account is already frozen
});

it('can unfreeze an account', function () {
    $account = Account::factory()->create(['frozen' => true]);

    $response = $this->postJson("/api/accounts/{$account->uuid}/unfreeze", [
        'reason' => 'Investigation completed',
        'authorized_by' => 'admin@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Account unfrozen successfully',
        ]);
});

it('cannot unfreeze an account that is not frozen', function () {
    $account = Account::factory()->create(['frozen' => false]);

    $response = $this->postJson("/api/accounts/{$account->uuid}/unfreeze", [
        'reason' => 'Invalid unfreeze attempt',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Account is not frozen',
            'error' => 'ACCOUNT_NOT_FROZEN',
        ]);

    // Workflow should not be dispatched when account is not frozen
});

it('cannot delete a frozen account', function () {
    $account = Account::factory()->create(['frozen' => true, 'balance' => 0]);

    $response = $this->deleteJson("/api/accounts/{$account->uuid}");

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Cannot delete frozen account',
            'error' => 'ACCOUNT_FROZEN',
        ]);

    // Destroy workflow should not be dispatched for frozen accounts
});

it('cannot deposit to a frozen account', function () {
    $account = Account::factory()->create(['frozen' => true]);

    $response = $this->postJson("/api/accounts/{$account->uuid}/deposit", [
        'amount' => 1000,
        'description' => 'Test deposit',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Cannot deposit to frozen account',
            'error' => 'ACCOUNT_FROZEN',
        ]);

    // Deposit workflow should not be dispatched for frozen accounts
});

it('cannot withdraw from a frozen account', function () {
    $account = Account::factory()->create(['frozen' => true, 'balance' => 5000]);

    $response = $this->postJson("/api/accounts/{$account->uuid}/withdraw", [
        'amount' => 1000,
        'description' => 'Test withdrawal',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Cannot deposit to frozen account',
            'error' => 'ACCOUNT_FROZEN',
        ]);

    // Withdraw workflow should not be dispatched for frozen accounts
});

it('cannot transfer from a frozen account', function () {
    $fromAccount = Account::factory()->create(['frozen' => true, 'balance' => 5000]);
    $toAccount = Account::factory()->create(['frozen' => false]);

    $response = $this->postJson('/api/transfers', [
        'from_account_uuid' => $fromAccount->uuid,
        'to_account_uuid' => $toAccount->uuid,
        'amount' => 1000,
        'description' => 'Test transfer',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Cannot transfer from frozen account',
            'error' => 'SOURCE_ACCOUNT_FROZEN',
        ]);

    // Transfer workflow should not be dispatched when source account is frozen
});

it('cannot transfer to a frozen account', function () {
    $fromAccount = Account::factory()->create(['frozen' => false, 'balance' => 5000]);
    $toAccount = Account::factory()->create(['frozen' => true]);

    $response = $this->postJson('/api/transfers', [
        'from_account_uuid' => $fromAccount->uuid,
        'to_account_uuid' => $toAccount->uuid,
        'amount' => 1000,
        'description' => 'Test transfer',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Cannot transfer to frozen account',
            'error' => 'DESTINATION_ACCOUNT_FROZEN',
        ]);

    // Transfer workflow should not be dispatched when source account is frozen
});

it('shows frozen status in account details', function () {
    $account = Account::factory()->create(['frozen' => true]);

    $response = $this->getJson("/api/accounts/{$account->uuid}");

    $response->assertOk()
        ->assertJsonPath('data.frozen', true);
});

it('shows frozen status in balance inquiry', function () {
    $account = Account::factory()->create(['frozen' => true]);

    $response = $this->getJson("/api/accounts/{$account->uuid}/balance");

    $response->assertOk()
        ->assertJsonPath('data.frozen', true);
});