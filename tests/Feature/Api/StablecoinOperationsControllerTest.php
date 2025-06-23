<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Account;
use App\Models\Stablecoin;
use App\Models\AccountBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StablecoinOperationsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Account $account;
    protected Stablecoin $stablecoin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'kyc_verified' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'kyc_verified' => true,
        ]);

        $this->account = Account::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->stablecoin = Stablecoin::factory()->create([
            'symbol' => 'FGCU',
            'name' => 'FinAegis Global Currency Unit',
            'peg_currency' => 'USD',
            'target_price' => 1.00,
            'is_active' => true,
        ]);

        // Create account balance for user
        AccountBalance::factory()->create([
            'account_uuid' => $this->account->uuid,
            'asset_code' => 'USD',
            'available_balance' => 10000.00,
            'reserved_balance' => 0.00,
        ]);
    }

    public function test_mint_stablecoin()
    {
        Queue::fake();

        $mintData = [
            'amount' => 1000.00,
            'collateral_asset' => 'USD',
            'slippage_tolerance' => 0.005, // 0.5%
            'max_price' => 1.005,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/stablecoins/{$this->stablecoin->symbol}/mint", $mintData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'operation_id',
                    'type',
                    'stablecoin_symbol',
                    'amount_requested',
                    'collateral_required',
                    'collateral_asset',
                    'estimated_rate',
                    'slippage_tolerance',
                    'fees' => [
                        'minting_fee',
                        'gas_fee_estimate',
                        'total_fees',
                    ],
                    'status',
                    'estimated_completion',
                    'workflow_id',
                ]
            ])
            ->assertJsonPath('data.type', 'mint')
            ->assertJsonPath('data.amount_requested', 1000.00);
    }

    public function test_redeem_stablecoin()
    {
        Queue::fake();

        // First create some stablecoin balance
        AccountBalance::factory()->create([
            'account_uuid' => $this->account->uuid,
            'asset_code' => $this->stablecoin->symbol,
            'available_balance' => 500.00,
            'reserved_balance' => 0.00,
        ]);

        $redeemData = [
            'amount' => 250.00,
            'target_asset' => 'USD',
            'slippage_tolerance' => 0.005,
            'min_price' => 0.995,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/stablecoins/{$this->stablecoin->symbol}/redeem", $redeemData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'operation_id',
                    'type',
                    'stablecoin_symbol',
                    'amount_to_redeem',
                    'target_asset',
                    'estimated_proceeds',
                    'estimated_rate',
                    'slippage_tolerance',
                    'fees' => [
                        'redemption_fee',
                        'gas_fee_estimate',
                        'total_fees',
                    ],
                    'status',
                    'estimated_completion',
                    'workflow_id',
                ]
            ])
            ->assertJsonPath('data.type', 'redeem')
            ->assertJsonPath('data.amount_to_redeem', 250.00);
    }

    public function test_get_minting_quote()
    {
        $quoteData = [
            'amount' => 1000.00,
            'collateral_asset' => 'USD',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/stablecoins/{$this->stablecoin->symbol}/mint-quote", $quoteData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'stablecoin_symbol',
                    'amount_to_mint',
                    'collateral_required',
                    'collateral_asset',
                    'exchange_rate',
                    'price_impact',
                    'fees' => [
                        'minting_fee',
                        'gas_fee_estimate',
                        'total_fees',
                    ],
                    'quote_validity' => [
                        'valid_until',
                        'quote_id',
                        'rate_locked',
                    ],
                    'market_conditions' => [
                        'liquidity_available',
                        'current_spread',
                        'market_stress_indicator',
                    ],
                    'user_limits' => [
                        'available_balance',
                        'daily_mint_remaining',
                        'max_single_mint',
                    ]
                ]
            ]);
    }

    public function test_get_redemption_quote()
    {
        // Create stablecoin balance
        AccountBalance::factory()->create([
            'account_uuid' => $this->account->uuid,
            'asset_code' => $this->stablecoin->symbol,
            'available_balance' => 1000.00,
        ]);

        $quoteData = [
            'amount' => 500.00,
            'target_asset' => 'USD',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/stablecoins/{$this->stablecoin->symbol}/redeem-quote", $quoteData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'stablecoin_symbol',
                    'amount_to_redeem',
                    'target_asset',
                    'estimated_proceeds',
                    'exchange_rate',
                    'price_impact',
                    'fees' => [
                        'redemption_fee',
                        'gas_fee_estimate',
                        'total_fees',
                    ],
                    'quote_validity' => [
                        'valid_until',
                        'quote_id',
                        'rate_locked',
                    ],
                    'market_conditions' => [
                        'redemption_capacity',
                        'current_spread',
                        'liquidity_stress',
                    ],
                    'user_limits' => [
                        'available_balance',
                        'daily_redeem_remaining',
                        'max_single_redeem',
                    ]
                ]
            ]);
    }

    public function test_get_operation_status()
    {
        $operationId = 'op_' . uniqid();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/accounts/{$this->account->uuid}/stablecoins/operations/{$operationId}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'operation_id',
                    'type',
                    'stablecoin_symbol',
                    'status',
                    'progress' => [
                        'current_step',
                        'total_steps',
                        'percentage_complete',
                        'estimated_completion',
                    ],
                    'transaction_details' => [
                        'amount_processed',
                        'actual_rate',
                        'fees_charged',
                        'gas_fees',
                        'transaction_hash',
                    ],
                    'workflow_details' => [
                        'workflow_id',
                        'activities' => [
                            '*' => [
                                'name',
                                'status',
                                'started_at',
                                'completed_at',
                                'retry_count',
                                'error_message',
                            ]
                        ]
                    ],
                    'compensation_info' => [
                        'enabled',
                        'rollback_plan',
                        'compensations_executed',
                    ]
                ]
            ]);
    }

    public function test_cancel_pending_operation()
    {
        $operationId = 'op_pending_' . uniqid();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/stablecoins/operations/{$operationId}/cancel", [
                'reason' => 'User requested cancellation',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'operation_id',
                    'status',
                    'cancellation_reason',
                    'cancelled_at',
                    'refund_details' => [
                        'amount_refunded',
                        'fees_refunded',
                        'processing_fee_retained',
                    ],
                    'workflow_termination' => [
                        'workflow_id',
                        'termination_status',
                        'compensation_executed',
                    ]
                ]
            ])
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_list_user_operations()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/accounts/{$this->account->uuid}/stablecoins/operations", [
                'status' => 'all',
                'limit' => 20,
                'stablecoin_symbol' => $this->stablecoin->symbol,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'operation_id',
                        'type',
                        'stablecoin_symbol',
                        'amount',
                        'status',
                        'created_at',
                        'completed_at',
                        'fees_paid',
                        'actual_rate',
                    ]
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'total_volume',
                    'total_fees_paid',
                    'operation_type_breakdown',
                ]
            ]);
    }

    public function test_insufficient_balance_for_minting()
    {
        // Update balance to insufficient amount
        AccountBalance::where('account_uuid', $this->account->uuid)
            ->where('asset_code', 'USD')
            ->update(['available_balance' => 100.00]);

        $mintData = [
            'amount' => 1000.00,
            'collateral_asset' => 'USD',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/stablecoins/{$this->stablecoin->symbol}/mint", $mintData);

        $response->assertUnprocessableEntity()
            ->assertJsonValidationErrors(['amount'])
            ->assertJsonPath('message', 'Insufficient balance for minting operation');
    }

    public function test_insufficient_stablecoin_balance_for_redemption()
    {
        // No stablecoin balance exists
        $redeemData = [
            'amount' => 250.00,
            'target_asset' => 'USD',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/stablecoins/{$this->stablecoin->symbol}/redeem", $redeemData);

        $response->assertUnprocessableEntity()
            ->assertJsonValidationErrors(['amount'])
            ->assertJsonPath('message', 'Insufficient stablecoin balance for redemption');
    }

    public function test_validate_mint_amount_limits()
    {
        $mintData = [
            'amount' => 50.00, // Below minimum
            'collateral_asset' => 'USD',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/stablecoins/{$this->stablecoin->symbol}/mint", $mintData);

        $response->assertUnprocessableEntity()
            ->assertJsonValidationErrors(['amount'])
            ->assertJsonPath('message', 'Amount below minimum minting threshold of 100.00');
    }

    public function test_validate_slippage_tolerance()
    {
        $mintData = [
            'amount' => 1000.00,
            'collateral_asset' => 'USD',
            'slippage_tolerance' => 0.15, // 15% - too high
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/stablecoins/{$this->stablecoin->symbol}/mint", $mintData);

        $response->assertUnprocessableEntity()
            ->assertJsonValidationErrors(['slippage_tolerance'])
            ->assertJsonPath('message', 'Slippage tolerance cannot exceed 5%');
    }

    public function test_emergency_redemption()
    {
        Queue::fake();

        // Create stablecoin balance
        AccountBalance::factory()->create([
            'account_uuid' => $this->account->uuid,
            'asset_code' => $this->stablecoin->symbol,
            'available_balance' => 1000.00,
        ]);

        $emergencyData = [
            'amount' => 500.00,
            'target_asset' => 'USD',
            'emergency_reason' => 'Suspected security breach',
            'bypass_normal_limits' => true,
            'accept_higher_slippage' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/stablecoins/{$this->stablecoin->symbol}/emergency-redeem", $emergencyData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'operation_id',
                    'type',
                    'priority',
                    'emergency_details' => [
                        'reason',
                        'expedited_processing',
                        'bypass_applied',
                        'higher_fees_applied',
                    ],
                    'estimated_completion',
                    'status',
                ]
            ])
            ->assertJsonPath('data.type', 'emergency_redeem')
            ->assertJsonPath('data.priority', 'high');
    }

    public function test_bulk_operations()
    {
        Queue::fake();

        $bulkData = [
            'operations' => [
                [
                    'type' => 'mint',
                    'amount' => 500.00,
                    'collateral_asset' => 'USD',
                ],
                [
                    'type' => 'mint',
                    'amount' => 300.00,
                    'collateral_asset' => 'EUR',
                ],
            ],
            'execution_strategy' => 'sequential',
            'fail_fast' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/stablecoins/{$this->stablecoin->symbol}/bulk-operations", $bulkData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'bulk_operation_id',
                    'total_operations',
                    'execution_strategy',
                    'estimated_completion',
                    'operations' => [
                        '*' => [
                            'operation_id',
                            'type',
                            'amount',
                            'status',
                            'position_in_queue',
                        ]
                    ],
                    'bulk_fees' => [
                        'total_fees',
                        'bulk_discount_applied',
                        'savings_amount',
                    ]
                ]
            ])
            ->assertJsonPath('data.total_operations', 2);
    }

    public function test_admin_can_force_mint_bypass_limits()
    {
        Queue::fake();

        $adminMintData = [
            'amount' => 50000.00, // Above normal user limits
            'collateral_asset' => 'USD',
            'bypass_user_limits' => true,
            'admin_override_reason' => 'Institutional client special allocation',
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/stablecoins/{$this->stablecoin->symbol}/admin-mint", $adminMintData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'operation_id',
                    'type',
                    'amount_requested',
                    'admin_override' => [
                        'limits_bypassed',
                        'override_reason',
                        'admin_user_id',
                        'additional_audit_required',
                    ],
                    'status',
                    'workflow_id',
                ]
            ])
            ->assertJsonPath('data.type', 'admin_mint');
    }

    public function test_get_operation_analytics()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/accounts/{$this->account->uuid}/stablecoins/operations/analytics", [
                'period' => '30d',
                'group_by' => 'type',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'total_operations',
                    'total_volume',
                    'total_fees_paid',
                    'operation_breakdown' => [
                        'mint' => [
                            'count',
                            'volume',
                            'average_amount',
                            'success_rate',
                        ],
                        'redeem' => [
                            'count',
                            'volume',
                            'average_amount',
                            'success_rate',
                        ]
                    ],
                    'trends' => [
                        'daily_volume',
                        'average_operation_size',
                        'fee_optimization_opportunities',
                    ],
                    'performance_metrics' => [
                        'average_completion_time',
                        'success_rate',
                        'retry_rate',
                        'user_satisfaction_score',
                    ]
                ]
            ]);
    }

    public function test_operation_fee_estimation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/accounts/{$this->account->uuid}/stablecoins/{$this->stablecoin->symbol}/estimate-fees", [
                'operation_type' => 'mint',
                'amount' => 1000.00,
                'collateral_asset' => 'USD',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'operation_type',
                    'amount',
                    'fee_breakdown' => [
                        'base_fee',
                        'percentage_fee',
                        'gas_fee_estimate',
                        'liquidity_fee',
                        'total_fees',
                    ],
                    'fee_comparison' => [
                        'current_fees',
                        'peak_hours_fees',
                        'off_peak_fees',
                        'bulk_operation_discount',
                    ],
                    'optimization_suggestions' => [
                        'better_timing',
                        'bulk_operation_benefits',
                        'alternative_assets',
                    ]
                ]
            ]);
    }

    public function test_unauthorized_access_to_other_user_operations()
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/accounts/{$otherAccount->uuid}/stablecoins/operations");

        $response->assertForbidden()
            ->assertJsonPath('message', 'Access denied to this account\'s stablecoin operations');
    }
}