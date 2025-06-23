<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Stablecoin;
use App\Models\StablecoinCollateralPosition;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StablecoinControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Stablecoin $stablecoin;
    protected Account $account;

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
    }

    public function test_list_available_stablecoins()
    {
        // Create additional stablecoins
        Stablecoin::factory()->create([
            'symbol' => 'FEUR',
            'name' => 'FinAegis Euro Stable',
            'peg_currency' => 'EUR',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/stablecoins');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'symbol',
                        'name',
                        'peg_currency',
                        'target_price',
                        'current_price',
                        'market_cap',
                        'total_supply',
                        'circulating_supply',
                        'collateralization_ratio',
                        'is_active',
                        'stability_metrics' => [
                            'price_deviation',
                            'volatility_24h',
                            'peg_stability_score',
                        ],
                        'created_at',
                    ]
                ],
                'meta' => [
                    'total_market_cap',
                    'total_stablecoins',
                    'average_stability_score',
                ]
            ]);
    }

    public function test_get_specific_stablecoin_details()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/stablecoins/{$this->stablecoin->symbol}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'symbol',
                    'name',
                    'description',
                    'peg_currency',
                    'target_price',
                    'current_price',
                    'price_history_24h' => [
                        '*' => [
                            'timestamp',
                            'price',
                            'volume',
                        ]
                    ],
                    'supply_metrics' => [
                        'total_supply',
                        'circulating_supply',
                        'reserved_supply',
                        'burned_supply',
                    ],
                    'collateral_details' => [
                        'total_collateral_value',
                        'collateralization_ratio',
                        'minimum_ratio_required',
                        'collateral_assets' => [
                            '*' => [
                                'asset_code',
                                'amount',
                                'value_usd',
                                'percentage_of_total',
                            ]
                        ]
                    ],
                    'stability_mechanisms' => [
                        'algorithmic_adjustments',
                        'manual_interventions',
                        'emergency_protocols',
                    ],
                    'audit_information' => [
                        'last_audit_date',
                        'auditor',
                        'compliance_status',
                        'reserve_verification',
                    ]
                ]
            ]);
    }

    public function test_get_stablecoin_price_history()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/stablecoins/{$this->stablecoin->symbol}/price-history", [
                'period' => '7d',
                'granularity' => 'hourly',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'symbol',
                    'period',
                    'granularity',
                    'price_data' => [
                        '*' => [
                            'timestamp',
                            'price',
                            'volume',
                            'deviation_from_peg',
                            'market_cap',
                        ]
                    ],
                    'statistics' => [
                        'average_price',
                        'max_price',
                        'min_price',
                        'max_deviation',
                        'min_deviation',
                        'average_deviation',
                        'volatility',
                        'stability_score',
                    ]
                ]
            ]);
    }

    public function test_get_collateralization_details()
    {
        // Create collateral positions
        StablecoinCollateralPosition::factory()->create([
            'stablecoin_id' => $this->stablecoin->id,
            'asset_code' => 'USD',
            'amount' => 1000000.00,
            'value_usd' => 1000000.00,
        ]);

        StablecoinCollateralPosition::factory()->create([
            'stablecoin_id' => $this->stablecoin->id,
            'asset_code' => 'EUR',
            'amount' => 800000.00,
            'value_usd' => 900000.00,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/stablecoins/{$this->stablecoin->symbol}/collateral");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'symbol',
                    'total_collateral_value',
                    'collateralization_ratio',
                    'target_ratio',
                    'minimum_ratio',
                    'collateral_breakdown' => [
                        '*' => [
                            'asset_code',
                            'amount',
                            'value_usd',
                            'percentage_of_total',
                            'asset_volatility',
                            'haircut_applied',
                        ]
                    ],
                    'risk_metrics' => [
                        'concentration_risk',
                        'liquidity_risk',
                        'market_risk',
                        'operational_risk',
                        'overall_risk_score',
                    ],
                    'stress_test_results' => [
                        'market_crash_scenario',
                        'liquidity_crisis_scenario',
                        'currency_devaluation_scenario',
                    ],
                    'rebalancing_needed',
                    'last_rebalancing',
                ]
            ]);
    }

    public function test_get_minting_redemption_rates()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/stablecoins/{$this->stablecoin->symbol}/rates");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'symbol',
                    'current_rates' => [
                        'minting_rate',
                        'redemption_rate',
                        'spread',
                        'effective_rate',
                    ],
                    'fees' => [
                        'minting_fee_percentage',
                        'redemption_fee_percentage',
                        'minimum_minting_fee',
                        'minimum_redemption_fee',
                    ],
                    'limits' => [
                        'minimum_mint_amount',
                        'maximum_mint_amount',
                        'daily_mint_limit',
                        'minimum_redemption_amount',
                        'maximum_redemption_amount',
                        'daily_redemption_limit',
                    ],
                    'market_conditions' => [
                        'liquidity_premium',
                        'stability_adjustment',
                        'market_stress_multiplier',
                    ],
                    'rate_validity' => [
                        'valid_until',
                        'update_frequency',
                        'next_update_estimated',
                    ]
                ]
            ]);
    }

    public function test_admin_can_view_internal_metrics()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/stablecoins/{$this->stablecoin->symbol}/internal-metrics");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'symbol',
                    'operational_metrics' => [
                        'daily_mint_volume',
                        'daily_redemption_volume',
                        'net_issuance',
                        'active_users',
                        'transaction_count',
                    ],
                    'stability_controls' => [
                        'algorithmic_interventions_24h',
                        'manual_interventions_24h',
                        'emergency_activations',
                        'circuit_breaker_triggers',
                    ],
                    'liquidity_metrics' => [
                        'available_liquidity',
                        'liquidity_utilization',
                        'redemption_capacity',
                        'emergency_reserves',
                    ],
                    'risk_monitoring' => [
                        'collateral_health_score',
                        'peg_deviation_alerts',
                        'liquidity_stress_indicators',
                        'operational_risk_flags',
                    ],
                    'compliance_status' => [
                        'regulatory_requirements_met',
                        'audit_schedule_compliance',
                        'reporting_obligations_current',
                        'license_status',
                    ]
                ]
            ]);
    }

    public function test_regular_user_cannot_view_internal_metrics()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/stablecoins/{$this->stablecoin->symbol}/internal-metrics");

        $response->assertForbidden()
            ->assertJsonPath('message', 'Admin privileges required to access internal metrics');
    }

    public function test_get_stability_report()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/stablecoins/{$this->stablecoin->symbol}/stability-report", [
                'period' => '30d',
                'include_analysis' => true,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'symbol',
                    'report_period',
                    'stability_overview' => [
                        'average_deviation',
                        'maximum_deviation',
                        'time_within_target_band',
                        'stability_score',
                        'improvement_trend',
                    ],
                    'peg_maintenance' => [
                        'interventions_count',
                        'intervention_effectiveness',
                        'average_restoration_time',
                        'mechanism_performance',
                    ],
                    'market_conditions_impact' => [
                        'correlation_with_markets',
                        'volatility_spillovers',
                        'external_stress_resilience',
                    ],
                    'comparative_analysis' => [
                        'peer_stablecoins_comparison',
                        'relative_performance',
                        'market_share_evolution',
                    ],
                    'recommendations' => [
                        'stability_improvements',
                        'risk_mitigation',
                        'operational_optimizations',
                    ]
                ]
            ]);
    }

    public function test_validate_symbol_parameter()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/stablecoins/INVALID_SYMBOL');

        $response->assertNotFound()
            ->assertJsonPath('message', 'Stablecoin not found');
    }

    public function test_get_stablecoin_events()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/stablecoins/{$this->stablecoin->symbol}/events", [
                'event_types' => ['mint', 'redeem', 'rebalance'],
                'limit' => 100,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'symbol',
                    'events' => [
                        '*' => [
                            'event_id',
                            'event_type',
                            'timestamp',
                            'amount',
                            'price',
                            'user_id',
                            'transaction_hash',
                            'gas_fee',
                            'status',
                            'additional_data',
                        ]
                    ],
                    'pagination' => [
                        'total',
                        'per_page',
                        'current_page',
                        'has_more',
                    ],
                    'summary' => [
                        'total_events',
                        'event_type_breakdown',
                        'total_volume',
                        'success_rate',
                    ]
                ]
            ]);
    }

    public function test_get_governance_information()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/stablecoins/{$this->stablecoin->symbol}/governance");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'symbol',
                    'governance_model' => [
                        'type',
                        'description',
                        'voting_mechanism',
                        'quorum_requirements',
                    ],
                    'governance_tokens' => [
                        'token_symbol',
                        'total_supply',
                        'circulating_supply',
                        'distribution_method',
                    ],
                    'active_proposals' => [
                        '*' => [
                            'proposal_id',
                            'title',
                            'description',
                            'proposal_type',
                            'voting_deadline',
                            'current_votes',
                            'status',
                        ]
                    ],
                    'voting_history' => [
                        '*' => [
                            'proposal_id',
                            'title',
                            'outcome',
                            'votes_for',
                            'votes_against',
                            'participation_rate',
                            'execution_date',
                        ]
                    ],
                    'governance_parameters' => [
                        'collateralization_ratio',
                        'stability_fees',
                        'liquidation_penalties',
                        'emergency_powers',
                    ]
                ]
            ]);
    }

    public function test_get_audit_reports()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/stablecoins/{$this->stablecoin->symbol}/audits");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'symbol',
                    'audit_reports' => [
                        '*' => [
                            'audit_id',
                            'auditor_name',
                            'audit_type',
                            'audit_date',
                            'report_url',
                            'findings_summary',
                            'compliance_score',
                            'recommendations_count',
                            'status',
                        ]
                    ],
                    'latest_audit' => [
                        'date',
                        'auditor',
                        'overall_rating',
                        'key_findings',
                        'action_items_resolved',
                        'next_audit_scheduled',
                    ],
                    'compliance_tracking' => [
                        'regulatory_compliance_score',
                        'security_audit_score',
                        'financial_audit_score',
                        'operational_audit_score',
                    ]
                ]
            ]);
    }

    public function test_rate_limiting_on_frequent_requests()
    {
        // Make multiple rapid requests to test rate limiting
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->actingAs($this->user, 'sanctum')
                ->getJson("/api/stablecoins/{$this->stablecoin->symbol}");
        }

        // All responses should be successful as they're within normal limits
        foreach ($responses as $response) {
            $response->assertOk();
        }
    }

    public function test_get_real_time_metrics()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/stablecoins/{$this->stablecoin->symbol}/real-time");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'symbol',
                    'real_time_data' => [
                        'current_price',
                        'target_price',
                        'deviation_percentage',
                        'last_update_timestamp',
                        'price_trend_1h',
                        'volume_24h',
                    ],
                    'market_activity' => [
                        'active_orders',
                        'recent_transactions',
                        'liquidity_depth',
                        'spread',
                    ],
                    'system_status' => [
                        'minting_enabled',
                        'redemption_enabled',
                        'emergency_mode',
                        'maintenance_scheduled',
                    ],
                    'streaming_available',
                    'update_frequency',
                ]
            ]);
    }
}