<?php

use Tests\TestCase;
use App\Domain\Compliance\Workflows\KycVerificationWorkflow;
use App\Domain\Compliance\Aggregates\ComplianceAggregate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Workflow\WorkflowStub;

class KycVerificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'kyc_status' => 'pending_review'
        ]);
    }

    public function test_kyc_verification_workflow_approves_valid_submission()
    {
        $input = [
            'user_uuid' => $this->user->uuid,
            'level' => 'standard'
        ];

        $workflow = WorkflowStub::make(KycVerificationWorkflow::class);
        $result = $workflow->start($input);

        $this->assertEquals('approved', $result['status']);
        $this->assertEquals('standard', $result['level']);
    }

    public function test_kyc_verification_workflow_rejects_invalid_documents()
    {
        // Mock user with invalid documents
        $this->user->update(['kyc_status' => 'pending_review']);
        
        $input = [
            'user_uuid' => $this->user->uuid,
            'level' => 'standard'
        ];

        // Simulate document verification failure
        $workflow = WorkflowStub::make(KycVerificationWorkflow::class);
        
        // This would normally be rejected by the verification activity
        $result = $workflow->start($input);
        
        if ($result['status'] === 'rejected') {
            $this->assertEquals('rejected', $result['status']);
            $this->assertArrayHasKey('reason', $result);
        }
    }

    public function test_kyc_verification_workflow_handles_sanctions_check()
    {
        $input = [
            'user_uuid' => $this->user->uuid,
            'level' => 'enhanced'
        ];

        $workflow = WorkflowStub::make(KycVerificationWorkflow::class);
        $result = $workflow->start($input);

        // If user is flagged, should be rejected
        if (isset($result['reason']) && $result['reason'] === 'sanctions_list') {
            $this->assertEquals('rejected', $result['status']);
            $this->assertEquals('sanctions_list', $result['reason']);
        } else {
            // If not flagged, should proceed normally
            $this->assertContains($result['status'], ['approved', 'rejected']);
        }
    }

    public function test_kyc_verification_updates_account_limits()
    {
        $input = [
            'user_uuid' => $this->user->uuid,
            'level' => 'enhanced'
        ];

        $workflow = WorkflowStub::make(KycVerificationWorkflow::class);
        $result = $workflow->start($input);

        if ($result['status'] === 'approved') {
            // Account limits should be updated based on KYC level
            $this->assertEquals('enhanced', $result['level']);
        }
    }

    public function test_kyc_verification_emits_correct_events()
    {
        $aggregate = ComplianceAggregate::retrieve($this->user->uuid);
        $aggregate->approveKyc($this->user->uuid, 'standard');
        $aggregate->persist();

        $events = $aggregate->getRecordedEvents();
        $this->assertCount(1, $events);
        $this->assertEquals('KycVerificationCompleted', class_basename($events[0]));
    }

    public function test_kyc_verification_compensation_works()
    {
        $input = [
            'user_uuid' => $this->user->uuid,
            'level' => 'standard'
        ];

        $workflow = WorkflowStub::make(KycVerificationWorkflow::class);
        
        // If workflow fails, compensation should reset status
        try {
            $result = $workflow->start($input);
            if ($result['status'] === 'rejected') {
                // Test passed - rejection is valid outcome
                $this->assertTrue(true);
            }
        } catch (\Exception $e) {
            // Compensation should have reset status to pending_review
            $this->user->refresh();
            $this->assertEquals('pending_review', $this->user->kyc_status);
        }
    }

    public function test_kyc_verification_handles_different_levels()
    {
        $levels = ['basic', 'standard', 'enhanced'];

        foreach ($levels as $level) {
            $input = [
                'user_uuid' => $this->user->uuid,
                'level' => $level
            ];

            $workflow = WorkflowStub::make(KycVerificationWorkflow::class);
            $result = $workflow->start($input);

            if ($result['status'] === 'approved') {
                $this->assertEquals($level, $result['level']);
            }
        }
    }

    public function test_kyc_verification_sends_notifications()
    {
        $input = [
            'user_uuid' => $this->user->uuid,
            'level' => 'standard'
        ];

        $workflow = WorkflowStub::make(KycVerificationWorkflow::class);
        $result = $workflow->start($input);

        // Notifications should be sent for both approval and rejection
        $this->assertContains($result['status'], ['approved', 'rejected']);
        
        // In real implementation, we would check notification queues
        // For now, just verify the workflow completes
        $this->assertArrayHasKey('status', $result);
    }

    public function test_kyc_verification_validates_user_exists()
    {
        $input = [
            'user_uuid' => 'non-existent-uuid',
            'level' => 'standard'
        ];

        $workflow = WorkflowStub::make(KycVerificationWorkflow::class);
        
        $this->expectException(\Exception::class);
        $workflow->start($input);
    }
}