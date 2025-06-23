<?php

namespace Tests\Feature\Api;

use App\Domain\Compliance\Services\RegulatoryReportingService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegulatoryReportingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        
        $this->admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);
    }

    public function test_generate_ctr_report_as_admin()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/regulatory/reports/ctr', [
                'date' => '2024-01-01'
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'date',
                    'filename',
                    'generated_at',
                    'download_url',
                ],
                'message'
            ]);
    }

    public function test_generate_ctr_report_as_regular_user_fails()
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/regulatory/reports/ctr', [
                'date' => '2024-01-01'
            ])
            ->assertStatus(403);
    }

    public function test_generate_sar_candidates_report_as_admin()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/regulatory/reports/sar-candidates', [
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31'
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'period_start',
                    'period_end',
                    'filename',
                    'generated_at',
                    'download_url',
                ],
                'message'
            ]);
    }

    public function test_generate_compliance_summary_as_admin()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/regulatory/reports/compliance-summary', [
                'month' => '2024-01'
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'month',
                    'filename',
                    'generated_at',
                    'download_url',
                ],
                'message'
            ]);
    }

    public function test_generate_kyc_report_as_admin()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/regulatory/reports/kyc')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'filename',
                    'generated_at',
                    'download_url',
                ],
                'message'
            ]);
    }

    public function test_list_reports_as_admin()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/regulatory/reports')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'reports',
                    'pagination' => [
                        'total',
                        'per_page',
                        'current_page',
                        'last_page',
                        'has_more',
                    ],
                ],
                'meta' => [
                    'available_types',
                    'total_reports',
                ]
            ]);
    }

    public function test_list_reports_with_type_filter()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/regulatory/reports?type=ctr&limit=10')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'reports',
                    'pagination',
                ],
                'meta'
            ]);
    }

    public function test_get_regulatory_metrics_as_admin()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/regulatory/metrics?period=month')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'period_start',
                    'period_end',
                    'metrics' => [
                        'kyc',
                        'transactions',
                        'users',
                        'risk',
                        'gdpr',
                    ],
                    'generated_at',
                ]
            ]);
    }

    public function test_get_regulatory_metrics_as_regular_user()
    {
        // Regular users can view metrics but not generate reports
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/regulatory/metrics')
            ->assertStatus(200);
    }

    public function test_invalid_date_format_validation()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/regulatory/reports/ctr', [
                'date' => 'invalid-date'
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_future_date_validation()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/regulatory/reports/ctr', [
                'date' => now()->addDay()->format('Y-m-d')
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_invalid_date_range_validation()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/regulatory/reports/sar-candidates', [
                'start_date' => '2024-01-31',
                'end_date' => '2024-01-01'
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_invalid_month_format_validation()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/regulatory/reports/compliance-summary', [
                'month' => '2024-13'  // Invalid month
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['month']);
    }

    public function test_unauthenticated_access_fails()
    {
        $this->postJson('/api/regulatory/reports/ctr', [
                'date' => '2024-01-01'
            ])
            ->assertStatus(401);

        $this->getJson('/api/regulatory/reports')
            ->assertStatus(401);

        $this->getJson('/api/regulatory/metrics')
            ->assertStatus(401);
    }

    public function test_get_specific_report_with_invalid_filename()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/regulatory/reports/invalid..filename')
            ->assertStatus(400)
            ->assertJson([
                'error' => 'Invalid filename format'
            ]);
    }

    public function test_get_nonexistent_report()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/regulatory/reports/nonexistent_report.json')
            ->assertStatus(404)
            ->assertJson([
                'error' => 'Report not found'
            ]);
    }

    public function test_delete_report_with_invalid_filename()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/regulatory/reports/invalid..filename')
            ->assertStatus(400)
            ->assertJson([
                'error' => 'Invalid filename format'
            ]);
    }

    public function test_delete_nonexistent_report()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/regulatory/reports/nonexistent_report.json')
            ->assertStatus(404)
            ->assertJson([
                'error' => 'Report not found'
            ]);
    }

    public function test_api_handles_service_exceptions_gracefully()
    {
        // Mock the service to throw an exception
        $this->mock(RegulatoryReportingService::class, function ($mock) {
            $mock->shouldReceive('generateCTR')
                ->andThrow(new \Exception('Service unavailable'));
        });

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/regulatory/reports/ctr', [
                'date' => '2024-01-01'
            ])
            ->assertStatus(500)
            ->assertJson([
                'error' => 'Failed to generate CTR report',
                'message' => 'Service unavailable'
            ]);
    }

    public function test_valid_period_values_for_metrics()
    {
        $validPeriods = ['week', 'month', 'quarter', 'year'];

        foreach ($validPeriods as $period) {
            $this->actingAs($this->admin, 'sanctum')
                ->getJson("/api/regulatory/metrics?period={$period}")
                ->assertStatus(200)
                ->assertJsonPath('data.period', $period);
        }
    }

    public function test_invalid_period_value_for_metrics()
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/regulatory/metrics?period=invalid')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }
}