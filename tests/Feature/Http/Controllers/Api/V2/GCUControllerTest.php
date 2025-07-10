<?php

namespace Tests\Feature\Http\Controllers\Api\V2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\ControllerTestCase;

class GCUControllerTest extends ControllerTestCase
{
    use RefreshDatabase;

    protected User $user;

    protected BasketAsset $gcu;

    protected array $assets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create GCU basket and its components
        $this->setupGCU();
    }

    protected function setupGCU(): void
    {
        // Create component assets
        $this->assets = [
            'USD' => Asset::firstOrCreate(
                ['code' => 'USD'],
                ['name' => 'US Dollar', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]
            ),
            'EUR' => Asset::firstOrCreate(
                ['code' => 'EUR'],
                ['name' => 'Euro', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]
            ),
            'GBP' => Asset::firstOrCreate(
                ['code' => 'GBP'],
                ['name' => 'British Pound', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]
            ),
        ];

        // Create GCU basket
        $this->gcu = BasketAsset::firstOrCreate(
            ['code' => 'GCU'],
            [
                'name'                => 'Global Currency Unit',
                'description'         => 'A basket of global currencies',
                'type'                => 'weighted',
                'rebalance_frequency' => 'quarterly',
                'is_active'           => true,
                'last_rebalanced_at'  => now()->subMonth(),
            ]
        );

        // Create basket components
        BasketComponent::firstOrCreate(
            ['basket_code' => 'GCU', 'asset_code' => 'USD'],
            ['weight' => 0.40, 'min_weight' => 0.35, 'max_weight' => 0.45, 'is_core' => true]
        );
        BasketComponent::firstOrCreate(
            ['basket_code' => 'GCU', 'asset_code' => 'EUR'],
            ['weight' => 0.35, 'min_weight' => 0.30, 'max_weight' => 0.40, 'is_core' => true]
        );
        BasketComponent::firstOrCreate(
            ['basket_code' => 'GCU', 'asset_code' => 'GBP'],
            ['weight' => 0.25, 'min_weight' => 0.20, 'max_weight' => 0.30, 'is_core' => true]
        );

        // Create a basket value record
        BasketValue::create([
            'basket_code'        => 'GCU',
            'value'              => 1.0975,
            'reference_currency' => 'USD',
            'component_values'   => [
                'USD' => ['value' => 1.0, 'weighted_value' => 0.40],
                'EUR' => ['value' => 1.08, 'weighted_value' => 0.378],
                'GBP' => ['value' => 1.27, 'weighted_value' => 0.3175],
            ],
            'calculated_at' => now(),
        ]);
    }

    #[Test]
    public function test_get_gcu_info_returns_current_data(): void
    {
        $response = $this->getJson('/api/v2/gcu');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'code',
                    'name',
                    'symbol',
                    'current_value',
                    'value_currency',
                    'last_rebalanced',
                    'next_rebalance',
                    'composition' => [
                        '*' => [
                            'asset_code',
                            'asset_name',
                            'weight',
                            'value_contribution',
                        ],
                    ],
                    'statistics',
                ],
            ])
            ->assertJson([
                'data' => [
                    'code'           => 'GCU',
                    'name'           => 'Global Currency Unit',
                    'symbol'         => 'Ǥ',
                    'current_value'  => 1.0975,
                    'value_currency' => 'USD',
                ],
            ])
            ->assertJsonCount(3, 'data.composition');
    }

    #[Test]
    public function test_get_value_history_returns_historical_data(): void
    {
        // Create historical values
        for ($i = 30; $i >= 0; $i--) {
            BasketValue::create([
                'basket_code'        => 'GCU',
                'value'              => 1.09 + ($i * 0.001),
                'reference_currency' => 'USD',
                'component_values'   => [],
                'calculated_at'      => now()->subDays($i),
            ]);
        }

        $response = $this->getJson('/api/v2/gcu/value-history?period=30d');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'basket_code',
                    'period',
                    'interval',
                    'currency',
                    'values' => [
                        '*' => [
                            'timestamp',
                            'value',
                            'change',
                            'change_percent',
                        ],
                    ],
                    'summary' => [
                        'start_value',
                        'end_value',
                        'high',
                        'low',
                        'total_change',
                        'total_change_percent',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'basket_code' => 'GCU',
                    'period'      => '30d',
                    'currency'    => 'USD',
                ],
            ]);
    }

    #[Test]
    public function test_get_value_history_validates_period(): void
    {
        $response = $this->getJson('/api/v2/gcu/value-history?period=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }

    #[Test]
    public function test_get_composition_details_returns_detailed_breakdown(): void
    {
        $response = $this->getJson('/api/v2/gcu/composition');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'basket_code',
                    'total_components',
                    'last_rebalanced',
                    'components' => [
                        '*' => [
                            'asset_code',
                            'asset_name',
                            'asset_type',
                            'current_weight',
                            'target_weight',
                            'weight_deviation',
                            'is_core',
                            'min_weight',
                            'max_weight',
                            'current_value',
                            'value_contribution',
                        ],
                    ],
                    'rebalance_needed',
                    'deviations' => [
                        'total_deviation',
                        'max_individual_deviation',
                        'components_out_of_range',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'basket_code'      => 'GCU',
                    'total_components' => 3,
                ],
            ]);
    }

    #[Test]
    public function test_project_rebalance_returns_simulation(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/gcu/project-rebalance', [
            'target_weights' => [
                'USD' => 0.38,
                'EUR' => 0.37,
                'GBP' => 0.25,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'simulation_id',
                    'basket_code',
                    'current_composition',
                    'proposed_composition',
                    'required_trades',
                    'impact' => [
                        'estimated_value_change',
                        'estimated_cost',
                        'execution_time',
                    ],
                    'warnings',
                ],
            ]);
    }

    #[Test]
    public function test_project_rebalance_requires_authentication(): void
    {
        $response = $this->postJson('/api/v2/gcu/project-rebalance');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_project_rebalance_validates_weights(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/gcu/project-rebalance', [
            'target_weights' => [
                'USD' => 0.50,
                'EUR' => 0.40,
                'GBP' => 0.20, // Sum is 1.10, should be 1.0
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_weights']);
    }

    #[Test]
    public function test_get_governance_info_returns_voting_data(): void
    {
        // Create a poll related to GCU
        Poll::create([
            'uuid'        => '550e8400-e29b-41d4-a716-446655440000',
            'type'        => 'basket_rebalance',
            'title'       => 'Q1 2025 GCU Rebalance',
            'description' => 'Vote on the proposed rebalancing of GCU',
            'metadata'    => ['basket_code' => 'GCU'],
            'options'     => ['approve', 'reject'],
            'status'      => 'active',
            'starts_at'   => now()->subDay(),
            'ends_at'     => now()->addDays(6),
        ]);

        $response = $this->getJson('/api/v2/gcu/governance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'active_proposals',
                    'completed_proposals',
                    'next_rebalance_vote',
                    'governance_stats' => [
                        'total_votes_cast',
                        'unique_voters',
                        'average_participation',
                    ],
                    'current_proposals' => [
                        '*' => [
                            'poll_uuid',
                            'title',
                            'type',
                            'status',
                            'ends_at',
                            'participation_rate',
                        ],
                    ],
                ],
            ]);
    }
}
