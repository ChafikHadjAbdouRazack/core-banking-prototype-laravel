<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\BasketAsset;
use App\Models\BasketPerformance;
use App\Models\ComponentPerformance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasketPerformanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected BasketAsset $basket;

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

        $this->basket = BasketAsset::factory()->create([
            'code' => 'GCU',
            'name' => 'Global Currency Unit',
            'type' => 'basket',
        ]);
    }

    public function test_get_basket_performance_overview()
    {
        // Create performance data
        BasketPerformance::factory()->create([
            'basket_id' => $this->basket->id,
            'performance_date' => now()->format('Y-m-d'),
            'total_value' => 1000000.00,
            'daily_return' => 0.025,
            'volatility' => 0.15,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/baskets/{$this->basket->code}/performance");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'basket_code',
                    'current_performance' => [
                        'total_value',
                        'daily_return',
                        'weekly_return',
                        'monthly_return',
                        'yearly_return',
                        'volatility',
                        'sharpe_ratio',
                        'max_drawdown',
                    ],
                    'risk_metrics' => [
                        'value_at_risk_95',
                        'value_at_risk_99',
                        'expected_shortfall',
                        'beta',
                        'correlation_to_benchmark',
                    ],
                    'composition_performance' => [
                        '*' => [
                            'asset_code',
                            'weight',
                            'contribution_to_return',
                            'individual_performance',
                        ]
                    ],
                    'last_updated',
                ]
            ]);
    }

    public function test_get_historical_performance()
    {
        // Create historical performance data
        for ($i = 30; $i >= 0; $i--) {
            BasketPerformance::factory()->create([
                'basket_id' => $this->basket->id,
                'performance_date' => now()->subDays($i)->format('Y-m-d'),
                'total_value' => 1000000 * (1 + ($i * 0.001)),
                'daily_return' => rand(-200, 300) / 10000, // -2% to 3%
            ]);
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/baskets/{$this->basket->code}/performance/history", [
                'period' => '30d',
                'granularity' => 'daily',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'basket_code',
                    'period',
                    'granularity',
                    'performance_data' => [
                        '*' => [
                            'date',
                            'total_value',
                            'daily_return',
                            'cumulative_return',
                            'volatility',
                        ]
                    ],
                    'summary_statistics' => [
                        'total_return',
                        'annualized_return',
                        'annualized_volatility',
                        'max_daily_gain',
                        'max_daily_loss',
                        'positive_days',
                        'negative_days',
                    ]
                ]
            ]);
    }

    public function test_get_component_performance_breakdown()
    {
        // Create component performance data
        ComponentPerformance::factory()->create([
            'basket_id' => $this->basket->id,
            'asset_code' => 'USD',
            'weight' => 0.30,
            'performance_contribution' => 0.008,
            'individual_return' => 0.025,
        ]);

        ComponentPerformance::factory()->create([
            'basket_id' => $this->basket->id,
            'asset_code' => 'EUR',
            'weight' => 0.25,
            'performance_contribution' => 0.006,
            'individual_return' => 0.024,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/baskets/{$this->basket->code}/performance/components");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'basket_code',
                    'components' => [
                        '*' => [
                            'asset_code',
                            'current_weight',
                            'target_weight',
                            'weight_drift',
                            'individual_performance' => [
                                'daily_return',
                                'weekly_return',
                                'monthly_return',
                                'volatility',
                            ],
                            'contribution_to_basket' => [
                                'return_contribution',
                                'risk_contribution',
                                'correlation_impact',
                            ],
                            'rebalancing_needed',
                        ]
                    ],
                    'rebalancing_analysis' => [
                        'total_drift',
                        'rebalancing_threshold',
                        'suggested_actions',
                        'cost_estimate',
                    ]
                ]
            ]);
    }

    public function test_get_risk_attribution()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/baskets/{$this->basket->code}/performance/risk-attribution");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'basket_code',
                    'total_portfolio_risk',
                    'risk_breakdown' => [
                        'currency_risk' => [
                            'percentage',
                            'contributors' => [
                                '*' => [
                                    'currency',
                                    'exposure',
                                    'risk_contribution',
                                ]
                            ]
                        ],
                        'market_risk' => [
                            'percentage',
                            'beta_exposure',
                            'sector_concentrations',
                        ],
                        'liquidity_risk' => [
                            'percentage',
                            'low_liquidity_assets',
                            'liquidity_score',
                        ],
                        'concentration_risk' => [
                            'percentage',
                            'largest_positions',
                            'herfindahl_index',
                        ]
                    ],
                    'diversification_metrics' => [
                        'effective_number_of_assets',
                        'diversification_ratio',
                        'correlation_matrix_summary',
                    ],
                    'stress_test_results' => [
                        'financial_crisis_scenario',
                        'currency_crisis_scenario',
                        'inflation_shock_scenario',
                    ]
                ]
            ]);
    }

    public function test_get_benchmark_comparison()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/baskets/{$this->basket->code}/performance/benchmark", [
                'benchmark' => 'MSCI_WORLD',
                'period' => '1y',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'basket_code',
                    'benchmark_code',
                    'comparison_period',
                    'performance_comparison' => [
                        'basket_return',
                        'benchmark_return',
                        'excess_return',
                        'tracking_error',
                        'information_ratio',
                        'hit_ratio',
                    ],
                    'risk_comparison' => [
                        'basket_volatility',
                        'benchmark_volatility',
                        'basket_beta',
                        'basket_alpha',
                        'correlation',
                    ],
                    'attribution_analysis' => [
                        'asset_allocation_effect',
                        'security_selection_effect',
                        'interaction_effect',
                        'total_active_return',
                    ],
                    'performance_chart_data' => [
                        '*' => [
                            'date',
                            'basket_cumulative_return',
                            'benchmark_cumulative_return',
                            'excess_return',
                        ]
                    ]
                ]
            ]);
    }

    public function test_admin_can_trigger_performance_calculation()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/baskets/{$this->basket->code}/performance/recalculate", [
                'force_recalculation' => true,
                'include_risk_metrics' => true,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'calculation_id',
                    'basket_code',
                    'status',
                    'estimated_completion',
                    'metrics_to_calculate' => [
                        'performance_returns',
                        'risk_metrics',
                        'attribution_analysis',
                        'benchmark_comparison',
                    ]
                ]
            ]);
    }

    public function test_regular_user_cannot_trigger_recalculation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/baskets/{$this->basket->code}/performance/recalculate");

        $response->assertForbidden()
            ->assertJsonPath('message', 'Admin privileges required for performance recalculation');
    }

    public function test_get_performance_alerts()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/baskets/{$this->basket->code}/performance/alerts");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'basket_code',
                    'active_alerts' => [
                        '*' => [
                            'alert_type',
                            'severity',
                            'triggered_at',
                            'description',
                            'threshold_value',
                            'current_value',
                            'suggested_actions',
                        ]
                    ],
                    'alert_configuration' => [
                        'volatility_threshold',
                        'drawdown_threshold',
                        'tracking_error_threshold',
                        'rebalancing_threshold',
                    ],
                    'notification_settings' => [
                        'email_alerts_enabled',
                        'dashboard_alerts_enabled',
                        'alert_frequency',
                    ]
                ]
            ]);
    }

    public function test_export_performance_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/baskets/{$this->basket->code}/performance/export", [
                'format' => 'csv',
                'date_range' => [
                    'start_date' => now()->subDays(30)->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                ],
                'include_components' => true,
                'include_risk_metrics' => true,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'export_id',
                    'format',
                    'status',
                    'estimated_completion',
                    'download_url',
                    'file_size_estimate',
                    'included_data' => [
                        'performance_data',
                        'component_breakdown',
                        'risk_metrics',
                        'benchmark_comparison',
                    ]
                ]
            ]);
    }

    public function test_get_performance_analytics()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/baskets/{$this->basket->code}/performance/analytics", [
                'analysis_type' => 'comprehensive',
                'lookback_period' => '1y',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'basket_code',
                    'analysis_period',
                    'key_insights' => [
                        'performance_summary',
                        'risk_assessment',
                        'portfolio_efficiency',
                        'improvement_opportunities',
                    ],
                    'statistical_analysis' => [
                        'return_distribution' => [
                            'mean',
                            'median',
                            'standard_deviation',
                            'skewness',
                            'kurtosis',
                        ],
                        'downside_statistics' => [
                            'downside_deviation',
                            'sortino_ratio',
                            'calmar_ratio',
                            'maximum_drawdown_duration',
                        ],
                        'tail_risk_measures' => [
                            'value_at_risk_95',
                            'conditional_value_at_risk',
                            'expected_tail_loss',
                        ]
                    ],
                    'regime_analysis' => [
                        'bull_market_performance',
                        'bear_market_performance',
                        'sideways_market_performance',
                        'regime_transition_impact',
                    ],
                    'attribution_insights' => [
                        'top_performers',
                        'worst_performers',
                        'consistency_analysis',
                        'rebalancing_impact',
                    ]
                ]
            ]);
    }

    public function test_validate_performance_query_parameters()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/baskets/{$this->basket->code}/performance/history", [
                'period' => 'invalid_period',
                'granularity' => 'invalid_granularity',
            ]);

        $response->assertUnprocessableEntity()
            ->assertJsonValidationErrors([
                'period',
                'granularity',
            ]);
    }

    public function test_performance_data_for_nonexistent_basket()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/baskets/NONEXISTENT/performance');

        $response->assertNotFound()
            ->assertJsonPath('message', 'Basket not found');
    }

    public function test_real_time_performance_updates()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/baskets/{$this->basket->code}/performance/live");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'basket_code',
                    'real_time_data' => [
                        'current_value',
                        'today_change_absolute',
                        'today_change_percentage',
                        'last_update_timestamp',
                        'market_status',
                    ],
                    'intraday_performance' => [
                        '*' => [
                            'timestamp',
                            'value',
                            'change_from_open',
                        ]
                    ],
                    'component_updates' => [
                        '*' => [
                            'asset_code',
                            'current_price',
                            'change_percentage',
                            'last_update',
                        ]
                    ],
                    'streaming_enabled',
                    'update_frequency',
                ]
            ]);
    }
}