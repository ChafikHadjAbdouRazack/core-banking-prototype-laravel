<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BatchProcessingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'kyc_verified' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'kyc_verified' => true,
        ]);
    }

    public function test_execute_batch_operation_as_admin()
    {
        Queue::fake();

        $batchData = [
            'operation_type' => 'account_interest',
            'parameters' => [
                'interest_rate' => 0.02,
                'apply_to_asset_codes' => ['USD', 'EUR'],
                'minimum_balance' => 1000.00,
            ],
            'schedule_for' => now()->addMinutes(5)->toISOString(),
            'compensation_enabled' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/batch-operations/execute', $batchData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'batch_id',
                    'operation_type',
                    'status',
                    'scheduled_for',
                    'created_at',
                    'estimated_completion',
                    'parameters' => [
                        'interest_rate',
                        'apply_to_asset_codes',
                        'minimum_balance',
                    ],
                    'compensation_settings' => [
                        'enabled',
                        'max_retry_attempts',
                        'rollback_strategy',
                    ]
                ]
            ])
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.operation_type', 'account_interest');
    }

    public function test_regular_user_cannot_execute_batch_operations()
    {
        $batchData = [
            'operation_type' => 'fee_collection',
            'parameters' => [
                'fee_percentage' => 0.001,
            ],
        ];

        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->postJson('/api/batch-operations/execute', $batchData);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Admin privileges required for batch operations');
    }

    public function test_list_batch_operations()
    {
        // Create some test batch operations in database (simulated)
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/batch-operations');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'batch_id',
                        'operation_type',
                        'status',
                        'created_at',
                        'completed_at',
                        'progress_percentage',
                        'records_processed',
                        'records_total',
                        'error_count',
                    ]
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'active_operations',
                    'completed_today',
                ]
            ]);
    }

    public function test_get_batch_operation_status()
    {
        $batchId = 'batch_' . uniqid();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/batch-operations/{$batchId}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'batch_id',
                    'operation_type',
                    'status',
                    'progress' => [
                        'percentage',
                        'current_step',
                        'total_steps',
                        'records_processed',
                        'records_total',
                        'estimated_completion',
                    ],
                    'results' => [
                        'successful_operations',
                        'failed_operations',
                        'skipped_operations',
                        'total_amount_processed',
                    ],
                    'logs' => [
                        '*' => [
                            'level',
                            'message',
                            'timestamp',
                            'context',
                        ]
                    ],
                    'compensation_status' => [
                        'enabled',
                        'rollbacks_executed',
                        'compensations_pending',
                    ]
                ]
            ]);
    }

    public function test_cancel_batch_operation()
    {
        $batchId = 'batch_' . uniqid();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/batch-operations/{$batchId}/cancel", [
                'reason' => 'User requested cancellation',
                'force_stop' => false,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'batch_id',
                    'status',
                    'cancellation_reason',
                    'cancelled_at',
                    'compensation_initiated',
                    'rollback_status',
                ]
            ])
            ->assertJsonPath('data.status', 'cancelling');
    }

    public function test_validate_batch_operation_parameters()
    {
        $invalidBatchData = [
            'operation_type' => 'invalid_operation',
            'parameters' => [], // Missing required parameters
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/batch-operations/execute', $invalidBatchData);

        $response->assertUnprocessableEntity()
            ->assertJsonValidationErrors([
                'operation_type',
                'parameters',
            ]);
    }

    public function test_account_interest_batch_operation()
    {
        Queue::fake();

        $batchData = [
            'operation_type' => 'account_interest',
            'parameters' => [
                'interest_rate' => 0.05, // 5% annual
                'apply_to_asset_codes' => ['USD', 'EUR'],
                'minimum_balance' => 100.00,
                'compound_frequency' => 'daily',
            ],
            'dry_run' => false,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/batch-operations/execute', $batchData);

        $response->assertCreated()
            ->assertJsonPath('data.operation_type', 'account_interest')
            ->assertJsonPath('data.parameters.interest_rate', 0.05);
    }

    public function test_fee_collection_batch_operation()
    {
        Queue::fake();

        $batchData = [
            'operation_type' => 'fee_collection',
            'parameters' => [
                'fee_type' => 'maintenance',
                'fee_amount' => 5.00,
                'asset_code' => 'USD',
                'apply_to_account_types' => ['personal', 'business'],
                'grace_period_days' => 3,
            ],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/batch-operations/execute', $batchData);

        $response->assertCreated()
            ->assertJsonPath('data.operation_type', 'fee_collection')
            ->assertJsonPath('data.parameters.fee_amount', 5.00);
    }

    public function test_balance_reconciliation_batch_operation()
    {
        Queue::fake();

        $batchData = [
            'operation_type' => 'balance_reconciliation',
            'parameters' => [
                'reconciliation_date' => now()->format('Y-m-d'),
                'asset_codes' => ['USD', 'EUR', 'GCU'],
                'variance_threshold' => 0.01, // 1% variance tolerance
                'auto_adjust' => false,
            ],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/batch-operations/execute', $batchData);

        $response->assertCreated()
            ->assertJsonPath('data.operation_type', 'balance_reconciliation')
            ->assertJsonPath('data.parameters.variance_threshold', 0.01);
    }

    public function test_report_generation_batch_operation()
    {
        Queue::fake();

        $batchData = [
            'operation_type' => 'report_generation',
            'parameters' => [
                'report_type' => 'regulatory_compliance',
                'date_range' => [
                    'start_date' => now()->subMonth()->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                ],
                'include_sections' => ['aml', 'kyc', 'transactions'],
                'format' => 'pdf',
                'delivery_method' => 'email',
            ],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/batch-operations/execute', $batchData);

        $response->assertCreated()
            ->assertJsonPath('data.operation_type', 'report_generation')
            ->assertJsonPath('data.parameters.report_type', 'regulatory_compliance');
    }

    public function test_dry_run_batch_operation()
    {
        Queue::fake();

        $batchData = [
            'operation_type' => 'account_interest',
            'parameters' => [
                'interest_rate' => 0.02,
                'apply_to_asset_codes' => ['USD'],
                'minimum_balance' => 1000.00,
            ],
            'dry_run' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/batch-operations/execute', $batchData);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'dry_run_completed')
            ->assertJsonStructure([
                'data' => [
                    'dry_run_results' => [
                        'affected_accounts_count',
                        'total_interest_amount',
                        'estimated_execution_time',
                        'potential_errors',
                        'resource_requirements',
                    ]
                ]
            ]);
    }

    public function test_batch_operation_progress_tracking()
    {
        $batchId = 'batch_' . uniqid();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/batch-operations/{$batchId}/progress");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'batch_id',
                    'current_status',
                    'progress_percentage',
                    'current_step',
                    'step_details' => [
                        'name',
                        'description',
                        'started_at',
                        'estimated_completion',
                    ],
                    'statistics' => [
                        'records_processed',
                        'records_remaining',
                        'success_rate',
                        'average_processing_time',
                    ],
                    'real_time_logs' => [
                        '*' => [
                            'timestamp',
                            'level',
                            'message',
                        ]
                    ]
                ]
            ]);
    }

    public function test_compensation_workflow_for_failed_batch()
    {
        $batchId = 'batch_failed_' . uniqid();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/batch-operations/{$batchId}/compensate", [
                'compensation_strategy' => 'rollback_all',
                'confirm_data_loss' => true,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'compensation_id',
                    'batch_id',
                    'strategy_applied',
                    'status',
                    'operations_to_reverse',
                    'estimated_completion',
                    'progress' => [
                        'percentage',
                        'operations_reversed',
                        'operations_remaining',
                    ]
                ]
            ])
            ->assertJsonPath('data.strategy_applied', 'rollback_all');
    }

    public function test_batch_operation_audit_log()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/batch-operations/audit-log', [
                'date_from' => now()->subWeek()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
                'operation_types' => ['account_interest', 'fee_collection'],
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'batch_id',
                        'operation_type',
                        'initiated_by',
                        'initiated_at',
                        'completed_at',
                        'status',
                        'records_affected',
                        'total_amount',
                        'compensation_executed',
                        'audit_trail' => [
                            '*' => [
                                'action',
                                'timestamp',
                                'user_id',
                                'details',
                            ]
                        ]
                    ]
                ],
                'meta' => [
                    'total_operations',
                    'success_rate',
                    'total_amount_processed',
                    'average_execution_time',
                ]
            ]);
    }
}