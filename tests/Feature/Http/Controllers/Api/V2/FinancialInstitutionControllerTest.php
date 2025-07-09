<?php

namespace Tests\Feature\Http\Controllers\Api\V2;

use App\Models\FinancialInstitutionApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinancialInstitutionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Storage::fake('applications');
    }

    public function test_get_application_form_returns_structure(): void
    {
        $response = $this->getJson('/api/v2/financial-institutions/application-form');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'institution_types',
                    'required_fields' => [
                        'institution_details',
                        'contact_information',
                        'regulatory_compliance',
                        'banking_relationships',
                        'technical_capabilities',
                    ],
                    'required_documents',
                    'optional_fields',
                ],
            ])
            ->assertJsonPath('data.institution_types.bank', 'Bank')
            ->assertJsonPath('data.institution_types.credit_union', 'Credit Union');
    }

    public function test_submit_application_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $applicationData = $this->getValidApplicationData();

        $response = $this->postJson('/api/v2/financial-institutions/applications', $applicationData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'application_id',
                    'status',
                    'submitted_at',
                    'next_steps',
                ],
            ])
            ->assertJson([
                'data' => [
                    'status' => 'pending_review',
                ],
            ]);

        $this->assertDatabaseHas('financial_institution_applications', [
            'institution_name' => $applicationData['institution_details']['institution_name'],
            'status'           => 'pending_review',
        ]);
    }

    public function test_submit_application_requires_authentication(): void
    {
        $applicationData = $this->getValidApplicationData();

        $response = $this->postJson('/api/v2/financial-institutions/applications', $applicationData);

        $response->assertStatus(401);
    }

    public function test_submit_application_validates_required_fields(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/financial-institutions/applications', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'institution_details',
                'contact_information',
                'regulatory_compliance',
            ]);
    }

    public function test_get_application_status(): void
    {
        Sanctum::actingAs($this->user);

        $application = FinancialInstitutionApplication::factory()->create([
            'user_uuid' => $this->user->uuid,
            'status'    => 'pending_review',
        ]);

        $response = $this->getJson("/api/v2/financial-institutions/applications/{$application->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'application_id',
                    'status',
                    'institution_name',
                    'submitted_at',
                    'last_updated_at',
                    'review_progress',
                    'pending_actions',
                    'documents',
                ],
            ])
            ->assertJson([
                'data' => [
                    'application_id' => $application->uuid,
                    'status'         => 'pending_review',
                ],
            ]);
    }

    public function test_get_application_status_requires_authentication(): void
    {
        $application = FinancialInstitutionApplication::factory()->create();

        $response = $this->getJson("/api/v2/financial-institutions/applications/{$application->uuid}");

        $response->assertStatus(401);
    }

    public function test_get_application_status_prevents_unauthorized_access(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $application = FinancialInstitutionApplication::factory()->create([
            'user_uuid' => $otherUser->uuid,
        ]);

        $response = $this->getJson("/api/v2/financial-institutions/applications/{$application->uuid}");

        $response->assertStatus(403);
    }

    public function test_upload_document_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $application = FinancialInstitutionApplication::factory()->create([
            'user_uuid' => $this->user->uuid,
        ]);

        $file = UploadedFile::fake()->create('license.pdf', 1000);

        $response = $this->postJson("/api/v2/financial-institutions/applications/{$application->uuid}/documents", [
            'document_type' => 'regulatory_license',
            'document'      => $file,
            'description'   => 'Banking license from regulatory authority',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'document_id',
                    'type',
                    'filename',
                    'uploaded_at',
                    'status',
                ],
            ])
            ->assertJson([
                'data' => [
                    'type'   => 'regulatory_license',
                    'status' => 'pending_verification',
                ],
            ]);

        Storage::disk('applications')->assertExists($application->uuid);
    }

    public function test_upload_document_validates_file_type(): void
    {
        Sanctum::actingAs($this->user);

        $application = FinancialInstitutionApplication::factory()->create([
            'user_uuid' => $this->user->uuid,
        ]);

        $file = UploadedFile::fake()->create('malicious.exe', 1000);

        $response = $this->postJson("/api/v2/financial-institutions/applications/{$application->uuid}/documents", [
            'document_type' => 'regulatory_license',
            'document'      => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document']);
    }

    public function test_withdraw_application(): void
    {
        Sanctum::actingAs($this->user);

        $application = FinancialInstitutionApplication::factory()->create([
            'user_uuid' => $this->user->uuid,
            'status'    => 'pending_review',
        ]);

        $response = $this->postJson("/api/v2/financial-institutions/applications/{$application->uuid}/withdraw", [
            'reason' => 'Changed business strategy',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'application_id' => $application->uuid,
                    'status'         => 'withdrawn',
                    'message'        => 'Application withdrawn successfully',
                ],
            ]);

        $this->assertDatabaseHas('financial_institution_applications', [
            'uuid'   => $application->uuid,
            'status' => 'withdrawn',
        ]);
    }

    public function test_withdraw_application_prevents_if_already_approved(): void
    {
        Sanctum::actingAs($this->user);

        $application = FinancialInstitutionApplication::factory()->create([
            'user_uuid' => $this->user->uuid,
            'status'    => 'approved',
        ]);

        $response = $this->postJson("/api/v2/financial-institutions/applications/{$application->uuid}/withdraw", [
            'reason' => 'Changed mind',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Cannot withdraw an approved application',
            ]);
    }

    private function getValidApplicationData(): array
    {
        return [
            'institution_details' => [
                'institution_name'          => 'Test Bank Ltd',
                'legal_name'                => 'Test Bank Limited',
                'registration_number'       => '12345678',
                'tax_id'                    => 'TB123456',
                'country'                   => 'GB',
                'institution_type'          => 'bank',
                'assets_under_management'   => 1000000000,
                'years_in_operation'        => 10,
                'primary_regulator'         => 'FCA',
                'regulatory_license_number' => 'FCA123456',
            ],
            'contact_information' => [
                'contact_name'             => 'John Doe',
                'contact_email'            => 'john@testbank.com',
                'contact_phone'            => '+441234567890',
                'contact_title'            => 'Chief Compliance Officer',
                'headquarters_address'     => '123 Bank Street, London',
                'headquarters_city'        => 'London',
                'headquarters_postal_code' => 'EC1A 1AA',
                'headquarters_country'     => 'GB',
            ],
            'regulatory_compliance' => [
                'has_banking_license'       => true,
                'license_jurisdictions'     => ['GB', 'EU'],
                'aml_program_in_place'      => true,
                'kyc_procedures_documented' => true,
                'data_protection_compliant' => true,
                'fatf_compliant'            => true,
            ],
            'banking_relationships' => [
                'primary_correspondent_bank' => 'HSBC',
                'swift_code'                 => 'TESTGB2L',
                'settlement_currencies'      => ['EUR', 'USD', 'GBP'],
            ],
            'technical_capabilities' => [
                'api_integration_experience' => true,
                'supported_protocols'        => ['REST', 'WebSocket'],
                'security_certifications'    => ['ISO27001', 'SOC2'],
            ],
        ];
    }
}
