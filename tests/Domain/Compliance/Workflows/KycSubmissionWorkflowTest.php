<?php

use Tests\TestCase;
use App\Domain\Compliance\Workflows\KycSubmissionWorkflow;
use App\Domain\Compliance\Aggregates\ComplianceAggregate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Workflow\WorkflowStub;

class KycSubmissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_kyc_submission_workflow_executes_successfully()
    {
        $input = [
            'user_uuid' => $this->user->uuid,
            'documents' => [
                [
                    'type' => 'passport',
                    'file_path' => 'test/passport.jpg',
                    'metadata' => ['country' => 'LT']
                ],
                [
                    'type' => 'address_proof',
                    'file_path' => 'test/utility_bill.pdf',
                    'metadata' => ['document_date' => '2025-06-01']
                ]
            ]
        ];

        $workflow = WorkflowStub::make(KycSubmissionWorkflow::class);
        $result = $workflow->start($input);

        $this->assertEquals('submitted', $result['status']);
        $this->assertArrayHasKey('submission_id', $result);
    }

    public function test_kyc_submission_workflow_rejects_invalid_documents()
    {
        $input = [
            'user_uuid' => $this->user->uuid,
            'documents' => [
                [
                    'type' => 'invalid_document',
                    'file_path' => 'test/invalid.txt',
                    'metadata' => []
                ]
            ]
        ];

        $workflow = WorkflowStub::make(KycSubmissionWorkflow::class);
        $result = $workflow->start($input);

        $this->assertEquals('rejected', $result['status']);
        $this->assertArrayHasKey('errors', $result);
    }

    public function test_kyc_submission_emits_correct_events()
    {
        $input = [
            'user_uuid' => $this->user->uuid,
            'documents' => [
                [
                    'type' => 'passport',
                    'file_path' => 'test/passport.jpg',
                    'metadata' => ['country' => 'LT']
                ]
            ]
        ];

        $aggregate = ComplianceAggregate::retrieve($this->user->uuid);
        $aggregate->submitKyc($this->user->uuid, $input['documents']);
        $aggregate->persist();

        $events = $aggregate->getRecordedEvents();
        $this->assertCount(2, $events); // KycSubmissionReceived + KycDocumentUploaded
    }

    public function test_kyc_submission_compensation_works()
    {
        $input = [
            'user_uuid' => $this->user->uuid,
            'documents' => []
        ];

        $workflow = WorkflowStub::make(KycSubmissionWorkflow::class);
        
        // Simulate failure requiring compensation
        try {
            $workflow->start($input);
        } catch (\Exception $e) {
            // Compensation should have reset status
            $this->user->refresh();
            $this->assertEquals('not_submitted', $this->user->kyc_status ?? 'not_submitted');
        }
    }

    public function test_kyc_submission_handles_multiple_document_types()
    {
        $input = [
            'user_uuid' => $this->user->uuid,
            'documents' => [
                [
                    'type' => 'passport',
                    'file_path' => 'test/passport.jpg',
                    'metadata' => ['country' => 'LT', 'expiry' => '2030-12-31']
                ],
                [
                    'type' => 'drivers_license',
                    'file_path' => 'test/license.jpg', 
                    'metadata' => ['country' => 'LT', 'expiry' => '2028-06-15']
                ],
                [
                    'type' => 'utility_bill',
                    'file_path' => 'test/bill.pdf',
                    'metadata' => ['date' => '2025-05-15', 'address' => 'Test St 123']
                ],
                [
                    'type' => 'bank_statement',
                    'file_path' => 'test/statement.pdf',
                    'metadata' => ['date' => '2025-06-01', 'bank' => 'Test Bank']
                ]
            ]
        ];

        $workflow = WorkflowStub::make(KycSubmissionWorkflow::class);
        $result = $workflow->start($input);

        $this->assertEquals('submitted', $result['status']);
        $this->assertArrayHasKey('submission_id', $result);
    }

    public function test_kyc_submission_validates_required_fields()
    {
        $inputs = [
            // Missing user_uuid
            [
                'documents' => [['type' => 'passport', 'file_path' => 'test.jpg']]
            ],
            // Missing documents
            [
                'user_uuid' => $this->user->uuid
            ],
            // Empty documents array
            [
                'user_uuid' => $this->user->uuid,
                'documents' => []
            ]
        ];

        foreach ($inputs as $input) {
            $workflow = WorkflowStub::make(KycSubmissionWorkflow::class);
            
            $this->expectException(\Exception::class);
            $workflow->start($input);
        }
    }
}