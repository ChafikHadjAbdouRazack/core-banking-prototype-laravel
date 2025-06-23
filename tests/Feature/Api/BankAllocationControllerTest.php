<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserBankPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAllocationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'kyc_verified' => true,
        ]);
    }

    public function test_get_user_bank_allocations()
    {
        // Create bank preferences
        UserBankPreference::factory()->create([
            'user_id' => $this->user->id,
            'bank_name' => 'Paysera',
            'allocation_percentage' => 40.0,
            'priority' => 1,
        ]);

        UserBankPreference::factory()->create([
            'user_id' => $this->user->id,
            'bank_name' => 'Deutsche Bank',
            'allocation_percentage' => 60.0,
            'priority' => 2,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/users/{$this->user->uuid}/bank-allocation");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'bank_name',
                        'allocation_percentage',
                        'priority',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'total_allocation',
                    'risk_distribution_score',
                    'insurance_coverage',
                ]
            ])
            ->assertJsonPath('meta.total_allocation', 100.0);
    }

    public function test_update_user_bank_allocations()
    {
        $allocations = [
            [
                'bank_name' => 'Paysera',
                'allocation_percentage' => 30.0,
                'priority' => 1,
            ],
            [
                'bank_name' => 'Deutsche Bank',
                'allocation_percentage' => 35.0,
                'priority' => 2,
            ],
            [
                'bank_name' => 'Santander',
                'allocation_percentage' => 35.0,
                'priority' => 3,
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/users/{$this->user->uuid}/bank-allocation", [
                'allocations' => $allocations,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'bank_name',
                        'allocation_percentage',
                        'priority',
                        'is_active',
                    ]
                ],
                'meta' => [
                    'total_allocation',
                    'changes_applied',
                    'risk_score_improvement',
                ]
            ]);

        // Verify database changes
        $this->assertDatabaseHas('user_bank_preferences', [
            'user_id' => $this->user->id,
            'bank_name' => 'Paysera',
            'allocation_percentage' => 30.0,
        ]);

        $this->assertDatabaseHas('user_bank_preferences', [
            'user_id' => $this->user->id,
            'bank_name' => 'Deutsche Bank',
            'allocation_percentage' => 35.0,
        ]);

        $this->assertDatabaseHas('user_bank_preferences', [
            'user_id' => $this->user->id,
            'bank_name' => 'Santander',
            'allocation_percentage' => 35.0,
        ]);
    }

    public function test_allocation_percentages_must_total_100()
    {
        $allocations = [
            [
                'bank_name' => 'Paysera',
                'allocation_percentage' => 50.0,
                'priority' => 1,
            ],
            [
                'bank_name' => 'Deutsche Bank',
                'allocation_percentage' => 40.0,
                'priority' => 2,
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/users/{$this->user->uuid}/bank-allocation", [
                'allocations' => $allocations,
            ]);

        $response->assertUnprocessableEntity()
            ->assertJsonValidationErrors(['allocations'])
            ->assertJsonPath('message', 'Total allocation percentage must equal 100%');
    }

    public function test_get_allocation_preview()
    {
        $allocations = [
            [
                'bank_name' => 'Paysera',
                'allocation_percentage' => 40.0,
                'priority' => 1,
            ],
            [
                'bank_name' => 'Deutsche Bank',
                'allocation_percentage' => 60.0,
                'priority' => 2,
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/users/{$this->user->uuid}/bank-allocation/preview", [
                'allocations' => $allocations,
                'total_amount' => 10000.00,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'distribution_preview' => [
                        '*' => [
                            'bank_name',
                            'allocated_amount',
                            'allocation_percentage',
                            'insurance_coverage',
                            'risk_level',
                        ]
                    ],
                    'risk_analysis' => [
                        'overall_risk_score',
                        'diversification_score',
                        'insurance_total',
                        'recommendations',
                    ],
                    'totals' => [
                        'total_amount',
                        'total_insured',
                        'total_uninsured',
                    ]
                ]
            ]);
    }

    public function test_get_recommended_allocations()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/users/{$this->user->uuid}/bank-allocation/recommendations", [
                'total_amount' => 50000.00,
                'risk_tolerance' => 'moderate',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'recommended_allocations' => [
                        '*' => [
                            'bank_name',
                            'allocation_percentage',
                            'priority',
                            'reasoning',
                        ]
                    ],
                    'strategy_explanation' => [
                        'name',
                        'description',
                        'benefits',
                        'considerations',
                    ],
                    'risk_profile' => [
                        'total_risk_score',
                        'diversification_level',
                        'insurance_coverage_ratio',
                    ]
                ]
            ]);
    }

    public function test_reset_to_default_allocations()
    {
        // Create existing allocations
        UserBankPreference::factory()->create([
            'user_id' => $this->user->id,
            'bank_name' => 'Paysera',
            'allocation_percentage' => 100.0,
            'priority' => 1,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/users/{$this->user->uuid}/bank-allocation/reset");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'bank_name',
                        'allocation_percentage',
                        'priority',
                        'is_active',
                    ]
                ],
                'meta' => [
                    'strategy_applied',
                    'changes_made',
                    'risk_improvement',
                ]
            ]);

        // Verify default multi-bank distribution was applied
        $preferences = $this->user->bankPreferences()->get();
        $this->assertGreaterThan(1, $preferences->count());
        $this->assertEquals(100.0, $preferences->sum('allocation_percentage'));
    }

    public function test_cannot_access_other_users_allocations()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/users/{$otherUser->uuid}/bank-allocation");

        $response->assertForbidden()
            ->assertJsonPath('message', 'Access denied to this user\'s bank allocation data');
    }

    public function test_unverified_kyc_user_cannot_update_allocations()
    {
        $unverifiedUser = User::factory()->create([
            'kyc_verified' => false,
        ]);

        $allocations = [
            [
                'bank_name' => 'Paysera',
                'allocation_percentage' => 100.0,
                'priority' => 1,
            ],
        ];

        $response = $this->actingAs($unverifiedUser, 'sanctum')
            ->putJson("/api/users/{$unverifiedUser->uuid}/bank-allocation", [
                'allocations' => $allocations,
            ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'KYC verification required to modify bank allocations');
    }

    public function test_admin_can_view_any_user_allocations()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Create allocations for regular user
        UserBankPreference::factory()->create([
            'user_id' => $this->user->id,
            'bank_name' => 'Paysera',
            'allocation_percentage' => 100.0,
            'priority' => 1,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/users/{$this->user->uuid}/bank-allocation");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'bank_name',
                        'allocation_percentage',
                        'priority',
                        'is_active',
                    ]
                ]
            ]);
    }

    public function test_get_allocation_history()
    {
        // Create some historical preferences
        UserBankPreference::factory()->create([
            'user_id' => $this->user->id,
            'bank_name' => 'Paysera',
            'allocation_percentage' => 100.0,
            'priority' => 1,
            'created_at' => now()->subDays(10),
        ]);

        UserBankPreference::factory()->create([
            'user_id' => $this->user->id,
            'bank_name' => 'Deutsche Bank',
            'allocation_percentage' => 50.0,
            'priority' => 1,
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/users/{$this->user->uuid}/bank-allocation/history");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'allocation_snapshot' => [
                            '*' => [
                                'bank_name',
                                'allocation_percentage',
                                'priority',
                            ]
                        ],
                        'created_at',
                        'risk_score',
                        'total_allocation',
                    ]
                ],
                'meta' => [
                    'total_changes',
                    'latest_change',
                    'risk_trend',
                ]
            ]);
    }

    public function test_validate_allocation_data_structure()
    {
        $invalidAllocations = [
            [
                'bank_name' => '', // Empty bank name
                'allocation_percentage' => 50.0,
                'priority' => 1,
            ],
            [
                'bank_name' => 'Deutsche Bank',
                'allocation_percentage' => -10.0, // Negative percentage
                'priority' => 2,
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/users/{$this->user->uuid}/bank-allocation", [
                'allocations' => $invalidAllocations,
            ]);

        $response->assertUnprocessableEntity()
            ->assertJsonValidationErrors([
                'allocations.0.bank_name',
                'allocations.1.allocation_percentage',
            ]);
    }

    public function test_maximum_banks_limit()
    {
        $allocations = [];
        for ($i = 1; $i <= 8; $i++) { // Exceeding max of 6 banks
            $allocations[] = [
                'bank_name' => "Bank {$i}",
                'allocation_percentage' => 12.5,
                'priority' => $i,
            ];
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/users/{$this->user->uuid}/bank-allocation", [
                'allocations' => $allocations,
            ]);

        $response->assertUnprocessableEntity()
            ->assertJsonValidationErrors(['allocations'])
            ->assertJsonPath('message', 'Maximum 6 banks allowed for allocation distribution');
    }
}