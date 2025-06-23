<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TransactionReversalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Account $account;
    protected Transaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'kyc_verified' => true,
        ]);

        $this->user = User::factory()->create([
            'kyc_verified' => true,
        ]);

        $this->account = Account::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->transaction = Transaction::factory()->create([
            'account_uuid' => $this->account->uuid,
            'amount' => 1000.00,
            'asset_code' => 'USD',
            'type' => 'deposit',
            'status' => 'completed',
        ]);
    }

    public function test_reverse_transaction_as_admin()
    {
        Queue::fake();

        $reversalData = [
            'reason' => 'Customer dispute - unauthorized transaction',
            'reversal_type' => 'full',
            'compensation_enabled' => true,
            'notify_customer' => true,
            'admin_notes' => 'Reviewed with customer service team',
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/transactions/reverse", $reversalData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'reversal_id',
                    'original_transaction_id',
                    'account_uuid',
                    'reversal_type',
                    'status',
                    'reason',
                    'initiated_by',
                    'initiated_at',
                    'compensation_workflow_id',
                    'amount_to_reverse',
                    'estimated_completion',
                ]
            ])
            ->assertJsonPath('data.reversal_type', 'full')
            ->assertJsonPath('data.status', 'initiated');
    }

    public function test_regular_user_cannot_reverse_transactions()
    {
        $reversalData = [
            'reason' => 'I want to reverse this transaction',
            'reversal_type' => 'full',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/transactions/reverse", $reversalData);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Admin privileges required for transaction reversals');
    }

    public function test_reverse_specific_transaction()
    {
        Queue::fake();

        $reversalData = [
            'transaction_id' => $this->transaction->uuid,
            'reason' => 'Duplicate transaction detected',
            'reversal_type' => 'full',
            'compensation_enabled' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/transactions/{$this->transaction->uuid}/reverse", $reversalData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'reversal_id',
                    'original_transaction' => [
                        'id',
                        'amount',
                        'asset_code',
                        'type',
                        'created_at',
                    ],
                    'reversal_transaction' => [
                        'id',
                        'amount',
                        'asset_code',
                        'type',
                        'reference',
                    ],
                    'compensation_details' => [
                        'workflow_id',
                        'activities_planned',
                        'rollback_strategy',
                    ]
                ]
            ]);
    }

    public function test_partial_transaction_reversal()
    {
        Queue::fake();

        $reversalData = [
            'transaction_id' => $this->transaction->uuid,
            'reason' => 'Partial refund approved',
            'reversal_type' => 'partial',
            'reversal_amount' => 250.00,
            'admin_notes' => 'Customer approved partial refund for service fee dispute',
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/transactions/{$this->transaction->uuid}/reverse", $reversalData);

        $response->assertCreated()
            ->assertJsonPath('data.reversal_type', 'partial')
            ->assertJsonPath('data.amount_to_reverse', 250.00)
            ->assertJsonStructure([
                'data' => [
                    'reversal_id',
                    'amount_to_reverse',
                    'remaining_amount',
                    'partial_reversal_details' => [
                        'original_amount',
                        'reversal_amount',
                        'remaining_amount',
                        'reversal_percentage',
                    ]
                ]
            ]);
    }

    public function test_get_reversal_history()
    {
        // Create some test reversal records
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/accounts/{$this->account->uuid}/transactions/reversals");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'reversal_id',
                        'original_transaction_id',
                        'reversal_type',
                        'status',
                        'reason',
                        'amount_reversed',
                        'initiated_by',
                        'initiated_at',
                        'completed_at',
                        'audit_trail_id',
                    ]
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'total_amount_reversed',
                    'reversal_success_rate',
                ]
            ]);
    }

    public function test_get_reversal_status()
    {
        $reversalId = 'reversal_' . uniqid();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/accounts/{$this->account->uuid}/transactions/reversals/{$reversalId}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'reversal_id',
                    'status',
                    'progress' => [
                        'current_step',
                        'total_steps',
                        'percentage_complete',
                        'estimated_completion',
                    ],
                    'workflow_details' => [
                        'workflow_id',
                        'activities' => [
                            '*' => [
                                'name',
                                'status',
                                'started_at',
                                'completed_at',
                                'error_message',
                            ]
                        ]
                    ],
                    'compensation_status' => [
                        'enabled',
                        'rollbacks_executed',
                        'compensations_pending',
                        'failures_detected',
                    ],
                    'audit_log' => [
                        '*' => [
                            'action',
                            'timestamp',
                            'user_id',
                            'details',
                            'ip_address',
                        ]
                    ]
                ]
            ]);
    }

    public function test_validate_reversal_conditions()
    {
        // Create a transaction that cannot be reversed (already reversed)
        $reversedTransaction = Transaction::factory()->create([
            'account_uuid' => $this->account->uuid,
            'status' => 'reversed',
            'amount' => 500.00,
        ]);

        $reversalData = [
            'transaction_id' => $reversedTransaction->uuid,
            'reason' => 'Attempting to reverse already reversed transaction',
            'reversal_type' => 'full',
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/transactions/{$reversedTransaction->uuid}/reverse", $reversalData);

        $response->assertUnprocessableEntity()
            ->assertJsonValidationErrors(['transaction_id'])
            ->assertJsonPath('message', 'Transaction has already been reversed and cannot be reversed again');
    }

    public function test_reversal_time_limit_validation()
    {
        // Create an old transaction
        $oldTransaction = Transaction::factory()->create([
            'account_uuid' => $this->account->uuid,
            'amount' => 1000.00,
            'created_at' => now()->subDays(91), // Beyond 90-day limit
        ]);

        $reversalData = [
            'transaction_id' => $oldTransaction->uuid,
            'reason' => 'Late reversal request',
            'reversal_type' => 'full',
            'override_time_limit' => false,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/transactions/{$oldTransaction->uuid}/reverse", $reversalData);

        $response->assertUnprocessableEntity()
            ->assertJsonPath('message', 'Transaction is beyond the 90-day reversal time limit');
    }

    public function test_override_time_limit_with_admin_approval()
    {
        Queue::fake();

        $oldTransaction = Transaction::factory()->create([
            'account_uuid' => $this->account->uuid,
            'amount' => 1000.00,
            'created_at' => now()->subDays(120),
        ]);

        $reversalData = [
            'transaction_id' => $oldTransaction->uuid,
            'reason' => 'Regulatory requirement - fraud investigation',
            'reversal_type' => 'full',
            'override_time_limit' => true,
            'override_justification' => 'Law enforcement request with valid court order',
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/transactions/{$oldTransaction->uuid}/reverse", $reversalData);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'initiated')
            ->assertJsonStructure([
                'data' => [
                    'reversal_id',
                    'override_applied' => [
                        'time_limit_overridden',
                        'justification',
                        'approved_by',
                        'additional_audit_required',
                    ]
                ]
            ]);
    }

    public function test_bulk_transaction_reversal()
    {
        Queue::fake();

        // Create multiple transactions
        $transactions = Transaction::factory()->count(3)->create([
            'account_uuid' => $this->account->uuid,
            'amount' => 500.00,
            'status' => 'completed',
        ]);

        $reversalData = [
            'transaction_ids' => $transactions->pluck('uuid')->toArray(),
            'reason' => 'Batch processing error - reversing affected transactions',
            'reversal_type' => 'full',
            'compensation_enabled' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/transactions/bulk-reverse", $reversalData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'bulk_reversal_id',
                    'total_transactions',
                    'total_amount_to_reverse',
                    'workflow_id',
                    'estimated_completion',
                    'transaction_reversals' => [
                        '*' => [
                            'transaction_id',
                            'reversal_id',
                            'amount',
                            'status',
                        ]
                    ]
                ]
            ])
            ->assertJsonPath('data.total_transactions', 3);
    }

    public function test_reversal_impact_analysis()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/transactions/{$this->transaction->uuid}/reversal-impact", [
                'reversal_type' => 'full',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'transaction_details' => [
                        'id',
                        'amount',
                        'asset_code',
                        'type',
                        'age_in_days',
                    ],
                    'impact_analysis' => [
                        'account_balance_impact',
                        'related_transactions_affected',
                        'downstream_effects' => [
                            'interest_calculations',
                            'fee_calculations',
                            'tax_implications',
                        ],
                        'compliance_considerations' => [
                            'aml_reporting_impact',
                            'regulatory_notifications_required',
                            'customer_notification_required',
                        ]
                    ],
                    'reversal_feasibility' => [
                        'can_reverse',
                        'complexity_level',
                        'estimated_processing_time',
                        'potential_complications',
                    ],
                    'recommendations' => [
                        'preferred_reversal_type',
                        'timing_considerations',
                        'additional_actions_required',
                    ]
                ]
            ]);
    }

    public function test_reversal_audit_trail()
    {
        $reversalId = 'reversal_' . uniqid();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/accounts/{$this->account->uuid}/transactions/reversals/{$reversalId}/audit");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'reversal_id',
                    'audit_trail' => [
                        '*' => [
                            'timestamp',
                            'action',
                            'performed_by' => [
                                'user_id',
                                'name',
                                'role',
                                'ip_address',
                            ],
                            'details' => [
                                'previous_state',
                                'new_state',
                                'changes_made',
                                'reason_codes',
                            ],
                            'verification' => [
                                'digital_signature',
                                'integrity_hash',
                                'witness_required',
                            ]
                        ]
                    ],
                    'compliance_markers' => [
                        'regulatory_notifications_sent',
                        'customer_notifications_sent',
                        'internal_approvals_obtained',
                        'external_verifications_completed',
                    ],
                    'data_integrity' => [
                        'audit_hash',
                        'tamper_evidence',
                        'backup_references',
                    ]
                ]
            ]);
    }

    public function test_emergency_reversal_workflow()
    {
        Queue::fake();

        $reversalData = [
            'transaction_id' => $this->transaction->uuid,
            'reason' => 'EMERGENCY: Suspected fraudulent activity',
            'reversal_type' => 'full',
            'emergency_mode' => true,
            'bypass_normal_workflow' => true,
            'emergency_authorization_code' => 'EMG-' . time(),
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/transactions/{$this->transaction->uuid}/emergency-reverse", $reversalData);

        $response->assertCreated()
            ->assertJsonPath('data.priority', 'emergency')
            ->assertJsonPath('data.status', 'initiated')
            ->assertJsonStructure([
                'data' => [
                    'reversal_id',
                    'priority',
                    'emergency_details' => [
                        'authorization_code',
                        'bypass_applied',
                        'escalation_level',
                        'notification_sent_to',
                    ],
                    'accelerated_timeline' => [
                        'normal_processing_time',
                        'emergency_processing_time',
                        'time_saved',
                    ]
                ]
            ]);
    }
}