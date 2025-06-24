<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    protected User $user;
    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->account = Account::factory()->forUser($this->user)->create();
    }

    public function test_can_list_transactions()
    {
        Sanctum::actingAs($this->user);

        // Create test transactions
        Transaction::factory()->count(3)->create([
            'account_uuid' => $this->account->uuid,
        ]);

        $response = $this->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'account_uuid',
                        'type',
                        'amount',
                        'status',
                        'created_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ]
            ]);
    }

    public function test_can_filter_transactions_by_account()
    {
        Sanctum::actingAs($this->user);

        $otherAccount = Account::factory()->forUser($this->user)->create();

        Transaction::factory()->count(2)->create(['account_uuid' => $this->account->uuid]);
        Transaction::factory()->create(['account_uuid' => $otherAccount->uuid]);

        $response = $this->getJson("/api/transactions?account_uuid={$this->account->uuid}");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        
        foreach ($response->json('data') as $transaction) {
            $this->assertEquals($this->account->uuid, $transaction['account_uuid']);
        }
    }

    public function test_can_filter_transactions_by_type()
    {
        Sanctum::actingAs($this->user);

        Transaction::factory()->create([
            'account_uuid' => $this->account->uuid,
            'type' => 'deposit'
        ]);
        Transaction::factory()->create([
            'account_uuid' => $this->account->uuid,
            'type' => 'withdrawal'
        ]);

        $response = $this->getJson('/api/transactions?type=deposit');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('deposit', $response->json('data.0.type'));
    }

    public function test_can_filter_transactions_by_status()
    {
        Sanctum::actingAs($this->user);

        Transaction::factory()->create([
            'account_uuid' => $this->account->uuid,
            'status' => 'completed'
        ]);
        Transaction::factory()->create([
            'account_uuid' => $this->account->uuid,
            'status' => 'pending'
        ]);

        $response = $this->getJson('/api/transactions?status=completed');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('completed', $response->json('data.0.status'));
    }

    public function test_can_show_specific_transaction()
    {
        Sanctum::actingAs($this->user);

        $transaction = Transaction::factory()->create([
            'account_uuid' => $this->account->uuid,
            'type' => 'deposit',
            'amount' => 10000,
            'status' => 'completed',
        ]);

        $response = $this->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'account_uuid',
                    'type',
                    'amount',
                    'status',
                    'metadata',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $transaction->id,
                    'account_uuid' => $this->account->uuid,
                    'type' => 'deposit',
                    'amount' => 10000,
                    'status' => 'completed',
                ]
            ]);
    }

    public function test_returns_404_for_nonexistent_transaction()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/transactions/999999');

        $response->assertStatus(404);
    }

    public function test_transaction_creation_not_implemented()
    {
        Sanctum::actingAs($this->user);

        $transactionData = [
            'account_uuid' => $this->account->uuid,
            'type' => 'deposit',
            'amount' => 5000,
            'description' => 'Test deposit',
        ];

        $response = $this->postJson('/api/transactions', $transactionData);

        // Transaction creation may not be implemented via POST
        $this->assertContains($response->status(), [404, 405, 422]);
    }

    public function test_can_update_transaction_status()
    {
        Sanctum::actingAs($this->user);

        $transaction = Transaction::factory()->create([
            'account_uuid' => $this->account->uuid,
            'status' => 'pending',
        ]);

        $response = $this->putJson("/api/transactions/{$transaction->id}", [
            'status' => 'completed',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'completed',
                ],
                'message' => 'Transaction updated successfully',
            ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'completed',
        ]);
    }

    public function test_pagination_works()
    {
        Sanctum::actingAs($this->user);

        Transaction::factory()->count(25)->create([
            'account_uuid' => $this->account->uuid,
        ]);

        $response = $this->getJson('/api/transactions?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertEquals(1, $response->json('meta.current_page'));
    }

    public function test_requires_authentication_for_all_endpoints()
    {
        $transaction = Transaction::factory()->create();

        // Test all endpoints require authentication
        $this->getJson('/api/transactions')->assertStatus(401);
        $this->getJson("/api/transactions/{$transaction->id}")->assertStatus(401);
        $this->postJson('/api/transactions', [])->assertStatus(401);
        $this->putJson("/api/transactions/{$transaction->id}", [])->assertStatus(401);
    }
}