<?php

namespace Tests\Unit\Domain\FinancialInstitution\Services;

use App\Domain\FinancialInstitution\Events\ApplicationApproved;
use App\Domain\FinancialInstitution\Events\ApplicationRejected;
use App\Domain\FinancialInstitution\Events\ApplicationSubmitted;
use App\Domain\FinancialInstitution\Exceptions\OnboardingException;
use App\Domain\FinancialInstitution\Services\ComplianceCheckService;
use App\Domain\FinancialInstitution\Services\DocumentVerificationService;
use App\Domain\FinancialInstitution\Services\OnboardingService;
use App\Domain\FinancialInstitution\Services\RiskAssessmentService;
use App\Models\FinancialInstitutionApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class OnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    private OnboardingService $service;
    private DocumentVerificationService $documentService;
    private ComplianceCheckService $complianceService;
    private RiskAssessmentService $riskService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->documentService = Mockery::mock(DocumentVerificationService::class);
        $this->complianceService = Mockery::mock(ComplianceCheckService::class);
        $this->riskService = Mockery::mock(RiskAssessmentService::class);
        
        $this->service = new OnboardingService(
            $this->documentService,
            $this->complianceService,
            $this->riskService
        );
        
        Event::fake();
    }

    public function test_submit_application_creates_application(): void
    {
        $data = [
            'institution_name' => 'Test Bank',
            'institution_type' => 'bank',
            'registration_number' => 'REG123456',
            'country' => 'US',
            'contact_email' => 'contact@testbank.com',
            'contact_phone' => '+1234567890',
        ];

        $application = $this->service->submitApplication($data);

        $this->assertInstanceOf(FinancialInstitutionApplication::class, $application);
        $this->assertEquals('Test Bank', $application->institution_name);
        $this->assertEquals('bank', $application->institution_type);
        $this->assertEquals('REG123456', $application->registration_number);
        $this->assertNotNull($application->required_documents);
        $this->assertNotNull($application->risk_score);
    }

    public function test_submit_application_dispatches_event(): void
    {
        $data = [
            'institution_name' => 'Event Test Bank',
            'institution_type' => 'fintech',
            'registration_number' => 'FT789012',
            'country' => 'UK',
            'contact_email' => 'info@eventbank.com',
        ];

        $application = $this->service->submitApplication($data);

        Event::assertDispatched(ApplicationSubmitted::class, function ($event) use ($application) {
            return $event->application->id === $application->id;
        });
    }

    public function test_submit_application_handles_exceptions(): void
    {
        // Force an exception by passing invalid data
        $this->expectException(OnboardingException::class);
        $this->expectExceptionMessage('Failed to submit application');

        Log::shouldReceive('error')->once();

        $this->service->submitApplication([]);
    }

    public function test_start_review_validates_reviewable_state(): void
    {
        $application = FinancialInstitutionApplication::factory()->create([
            'status' => 'approved', // Not reviewable
        ]);

        $this->expectException(OnboardingException::class);
        $this->expectExceptionMessage('Application is not in a reviewable state');

        $this->service->startReview($application, 'reviewer-123');
    }

    public function test_start_review_updates_application(): void
    {
        $application = FinancialInstitutionApplication::factory()->create([
            'status' => 'pending',
        ]);

        $this->service->startReview($application, 'reviewer-456');

        $application->refresh();
        $this->assertEquals('under_review', $application->status);
        $this->assertEquals('reviewer-456', $application->reviewer_id);
        $this->assertNotNull($application->review_started_at);
    }

    public function test_approve_application_with_all_checks_passed(): void
    {
        $application = FinancialInstitutionApplication::factory()->create([
            'status' => 'under_review',
        ]);

        // Mock all checks as passed
        $this->documentService->shouldReceive('allDocumentsVerified')
            ->with($application)
            ->andReturn(true);
            
        $this->complianceService->shouldReceive('allChecksPassed')
            ->with($application)
            ->andReturn(true);
            
        $this->riskService->shouldReceive('isAcceptableRisk')
            ->with($application)
            ->andReturn(true);

        $result = $this->service->approveApplication($application, 'approver-123', 'All checks passed');

        $this->assertTrue($result);
        $application->refresh();
        $this->assertEquals('approved', $application->status);
        $this->assertEquals('approver-123', $application->approved_by);
        $this->assertNotNull($application->approved_at);
        
        Event::assertDispatched(ApplicationApproved::class);
    }

    public function test_approve_application_fails_without_document_verification(): void
    {
        $application = FinancialInstitutionApplication::factory()->create([
            'status' => 'under_review',
        ]);

        $this->documentService->shouldReceive('allDocumentsVerified')
            ->andReturn(false);

        $this->expectException(OnboardingException::class);
        $this->expectExceptionMessage('Cannot approve: All documents must be verified');

        $this->service->approveApplication($application, 'approver-123', 'Notes');
    }

    public function test_approve_application_fails_without_compliance_checks(): void
    {
        $application = FinancialInstitutionApplication::factory()->create([
            'status' => 'under_review',
        ]);

        $this->documentService->shouldReceive('allDocumentsVerified')->andReturn(true);
        $this->complianceService->shouldReceive('allChecksPassed')->andReturn(false);

        $this->expectException(OnboardingException::class);
        $this->expectExceptionMessage('Cannot approve: Compliance checks have not passed');

        $this->service->approveApplication($application, 'approver-123', 'Notes');
    }

    public function test_approve_application_fails_with_high_risk(): void
    {
        $application = FinancialInstitutionApplication::factory()->create([
            'status' => 'under_review',
        ]);

        $this->documentService->shouldReceive('allDocumentsVerified')->andReturn(true);
        $this->complianceService->shouldReceive('allChecksPassed')->andReturn(true);
        $this->riskService->shouldReceive('isAcceptableRisk')->andReturn(false);

        $this->expectException(OnboardingException::class);
        $this->expectExceptionMessage('Cannot approve: Risk level is too high');

        $this->service->approveApplication($application, 'approver-123', 'Notes');
    }

    public function test_reject_application(): void
    {
        $application = FinancialInstitutionApplication::factory()->create([
            'status' => 'under_review',
        ]);

        $reason = 'Incomplete documentation';
        $result = $this->service->rejectApplication($application, 'rejector-123', $reason);

        $this->assertTrue($result);
        $application->refresh();
        $this->assertEquals('rejected', $application->status);
        $this->assertEquals($reason, $application->rejection_reason);
        $this->assertEquals('rejector-123', $application->rejected_by);
        $this->assertNotNull($application->rejected_at);
        
        Event::assertDispatched(ApplicationRejected::class);
    }

    public function test_activate_partner_creates_partner_entity(): void
    {
        $application = FinancialInstitutionApplication::factory()->create([
            'status' => 'approved',
            'institution_name' => 'Partner Bank',
        ]);

        $partner = $this->service->activatePartner($application);

        $this->assertInstanceOf(\App\Models\FinancialInstitutionPartner::class, $partner);
        $this->assertEquals($application->id, $partner->application_id);
        $this->assertEquals('Partner Bank', $partner->name);
        $this->assertEquals('active', $partner->status);
        $this->assertTrue($partner->is_active);
        
        Event::assertDispatched(\App\Domain\FinancialInstitution\Events\PartnerActivated::class);
    }

    public function test_activate_partner_requires_approved_application(): void
    {
        $application = FinancialInstitutionApplication::factory()->create([
            'status' => 'pending',
        ]);

        $this->expectException(OnboardingException::class);
        $this->expectExceptionMessage('Can only activate approved applications');

        $this->service->activatePartner($application);
    }

    public function test_get_application_progress(): void
    {
        $application = FinancialInstitutionApplication::factory()->create();

        // Mock service responses
        $this->documentService->shouldReceive('getVerificationProgress')
            ->with($application)
            ->andReturn(['verified' => 3, 'total' => 5]);
            
        $this->complianceService->shouldReceive('getCheckProgress')
            ->with($application)
            ->andReturn(['completed' => 2, 'total' => 4]);

        $progress = $this->service->getApplicationProgress($application);

        $this->assertArrayHasKey('documents', $progress);
        $this->assertArrayHasKey('compliance', $progress);
        $this->assertArrayHasKey('overall', $progress);
        $this->assertEquals(3, $progress['documents']['verified']);
        $this->assertEquals(5, $progress['documents']['total']);
        $this->assertEquals(2, $progress['compliance']['completed']);
        $this->assertEquals(4, $progress['compliance']['total']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}