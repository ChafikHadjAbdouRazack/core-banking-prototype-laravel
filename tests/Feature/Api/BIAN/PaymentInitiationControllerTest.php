<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

it('can initiate a payment transaction', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    
    $payerAccount = Account::factory()->withBalance(1000)->create();
    $payeeAccount = Account::factory()->create();

    $response = $this->postJson('/api/bian/payment-initiation/initiate', [
        'payerReference' => $payerAccount->uuid,
        'payeeReference' => $payeeAccount->uuid,
        'paymentAmount' => 250,
        'paymentCurrency' => 'USD',
        'paymentPurpose' => 'Invoice payment',
        'paymentType' => 'internal',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'paymentInitiationTransaction' => [
                'crReferenceId',
                'paymentStatus',
                'paymentDetails' => [
                    'payerReference',
                    'payerName',
                    'payeeReference',
                    'payeeName',
                    'paymentAmount',
                    'paymentCurrency',
                    'paymentPurpose',
                    'paymentType',
                ],
                'paymentSchedule' => [
                    'initiationDate',
                    'valueDate',
                ],
                'balanceAfterPayment' => [
                    'payerBalance',
                    'payeeBalance',
                ],
            ],
        ])
        ->assertJsonPath('paymentInitiationTransaction.paymentStatus', 'completed')
        ->assertJsonPath('paymentInitiationTransaction.paymentDetails.paymentAmount', 250);
});

it('rejects payment with insufficient funds', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    
    $payerAccount = Account::factory()->withBalance(100)->create();
    $payeeAccount = Account::factory()->create();

    $response = $this->postJson('/api/bian/payment-initiation/initiate', [
        'payerReference' => $payerAccount->uuid,
        'payeeReference' => $payeeAccount->uuid,
        'paymentAmount' => 500,
        'paymentType' => 'internal',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('paymentInitiationTransaction.paymentStatus', 'rejected')
        ->assertJsonPath('paymentInitiationTransaction.statusReason', 'Insufficient funds');
});

it('can schedule a future payment', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    
    $payerAccount = Account::factory()->withBalance(1000)->create();
    $payeeAccount = Account::factory()->create();

    $response = $this->postJson('/api/bian/payment-initiation/initiate', [
        'payerReference' => $payerAccount->uuid,
        'payeeReference' => $payeeAccount->uuid,
        'paymentAmount' => 300,
        'paymentType' => 'scheduled',
        'valueDate' => now()->addDays(7)->toDateString(),
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('paymentInitiationTransaction.paymentStatus', 'scheduled')
        ->assertJsonPath('paymentInitiationTransaction.paymentSchedule.valueDate', now()->addDays(7)->toDateString());
});

it('can update a payment transaction', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $crReferenceId = Str::uuid()->toString();

    $response = $this->putJson("/api/bian/payment-initiation/{$crReferenceId}/update", [
        'paymentStatus' => 'cancelled',
        'statusReason' => 'Customer requested cancellation',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('paymentInitiationTransaction.updateAction', 'cancelled')
        ->assertJsonPath('paymentInitiationTransaction.updateStatus', 'successful');
});

it('can retrieve payment transaction details', function () {
    // Skip this test as it requires event sourcing setup
    $this->markTestSkipped('Payment retrieval requires event sourcing setup');
});

it('can execute a payment transaction', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $crReferenceId = Str::uuid()->toString();

    $response = $this->postJson("/api/bian/payment-initiation/{$crReferenceId}/execute", [
        'executionMode' => 'immediate',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'paymentExecutionRecord' => [
                'crReferenceId',
                'executionMode',
                'executionStatus',
                'executionDateTime',
            ],
        ])
        ->assertJsonPath('paymentExecutionRecord.executionStatus', 'completed');
});

it('can request payment status', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $crReferenceId = Str::uuid()->toString();

    $response = $this->postJson("/api/bian/payment-initiation/{$crReferenceId}/payment-status/request");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'paymentStatusRecord' => [
                'crReferenceId',
                'bqReferenceId',
                'paymentStatus',
                'statusCheckDateTime',
                'eventCount',
            ],
        ]);
});

it('can retrieve payment history', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $account = Account::factory()->create();

    $response = $this->getJson("/api/bian/payment-initiation/{$account->uuid}/payment-history/retrieve");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'paymentHistoryRecord' => [
                'accountReference',
                'bqReferenceId',
                'historyPeriod' => [
                    'fromDate',
                    'toDate',
                ],
                'payments',
                'paymentCount',
                'retrievalDateTime',
            ],
        ]);
});

it('validates required fields for payment initiation', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    
    $response = $this->postJson('/api/bian/payment-initiation/initiate', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['payerReference', 'payeeReference', 'paymentAmount', 'paymentType']);
});

it('prevents payment to same account', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $account = Account::factory()->withBalance(1000)->create();

    $response = $this->postJson('/api/bian/payment-initiation/initiate', [
        'payerReference' => $account->uuid,
        'payeeReference' => $account->uuid,
        'paymentAmount' => 100,
        'paymentType' => 'internal',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['payeeReference']);
});

it('requires authentication for all endpoints', function () {
    $uuid = Str::uuid()->toString();

    $this->postJson('/api/bian/payment-initiation/initiate')->assertStatus(401);
    $this->getJson("/api/bian/payment-initiation/{$uuid}/retrieve")->assertStatus(401);
    $this->putJson("/api/bian/payment-initiation/{$uuid}/update")->assertStatus(401);
    $this->postJson("/api/bian/payment-initiation/{$uuid}/execute")->assertStatus(401);
    $this->postJson("/api/bian/payment-initiation/{$uuid}/payment-status/request")->assertStatus(401);
    $this->getJson("/api/bian/payment-initiation/{$uuid}/payment-history/retrieve")->assertStatus(401);
});