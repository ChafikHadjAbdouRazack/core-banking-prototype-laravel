<?php

namespace Tests\Unit\Domain\Compliance\Aggregates;

use App\Domain\Compliance\Aggregates\ComplianceAggregate;
use App\Domain\Compliance\Events\GdprDataDeleted;
use App\Domain\Compliance\Events\GdprDataExported;
use App\Domain\Compliance\Events\GdprRequestReceived;
use App\Domain\Compliance\Events\KycDocumentUploaded;
use App\Domain\Compliance\Events\KycSubmissionReceived;
use App\Domain\Compliance\Events\KycVerificationCompleted;
use App\Domain\Compliance\Events\KycVerificationRejected;
use App\Domain\Compliance\Events\RegulatoryReportGenerated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceAggregateTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_kyc_records_submission_and_document_events(): void
    {
        $aggregate = ComplianceAggregate::fake();
        $userUuid = 'user-123';
        
        $documents = [
            ['type' => 'passport', 'filename' => 'passport.jpg'],
            ['type' => 'proof_of_address', 'filename' => 'utility.pdf'],
        ];

        $aggregate->submitKyc($userUuid, $documents);

        // Should record one submission event
        $aggregate->assertRecorded([
            new KycSubmissionReceived($userUuid, $documents),
        ]);

        // Should record document events for each document
        foreach ($documents as $document) {
            $aggregate->assertRecorded(
                new KycDocumentUploaded($userUuid, $document)
            );
        }
    }

    public function test_approve_kyc_records_verification_completed(): void
    {
        $aggregate = ComplianceAggregate::fake();
        $userUuid = 'user-456';
        $level = 'enhanced';

        $aggregate->approveKyc($userUuid, $level);

        $aggregate->assertRecorded([
            new KycVerificationCompleted($userUuid, $level),
        ]);
    }

    public function test_reject_kyc_records_verification_rejected(): void
    {
        $aggregate = ComplianceAggregate::fake();
        $userUuid = 'user-789';
        $reason = 'Invalid documents provided';

        $aggregate->rejectKyc($userUuid, $reason);

        $aggregate->assertRecorded([
            new KycVerificationRejected($userUuid, $reason),
        ]);
    }

    public function test_request_gdpr_export_records_request_event(): void
    {
        $aggregate = ComplianceAggregate::fake();
        $userUuid = 'user-gdpr-1';
        $options = ['include_transactions' => true, 'format' => 'json'];

        $aggregate->requestGdprExport($userUuid, $options);

        $aggregate->assertRecorded([
            new GdprRequestReceived($userUuid, 'export', $options),
        ]);
    }

    public function test_complete_gdpr_export_records_export_event(): void
    {
        $aggregate = ComplianceAggregate::fake();
        $userUuid = 'user-gdpr-2';
        $filePath = '/exports/user-gdpr-2-20240115.zip';

        $aggregate->completeGdprExport($userUuid, $filePath);

        $aggregate->assertRecorded([
            new GdprDataExported($userUuid, $filePath),
        ]);
    }

    public function test_request_gdpr_deletion_records_request_event(): void
    {
        $aggregate = ComplianceAggregate::fake();
        $userUuid = 'user-delete-1';
        $reason = 'User requested account deletion';

        $aggregate->requestGdprDeletion($userUuid, $reason);

        $aggregate->assertRecorded([
            new GdprRequestReceived($userUuid, 'deletion', ['reason' => $reason]),
        ]);
    }

    public function test_complete_gdpr_deletion_records_deletion_event(): void
    {
        $aggregate = ComplianceAggregate::fake();
        $userUuid = 'user-delete-2';

        $aggregate->completeGdprDeletion($userUuid);

        $aggregate->assertRecorded([
            new GdprDataDeleted($userUuid),
        ]);
    }

    public function test_generate_regulatory_report_records_report_event(): void
    {
        $aggregate = ComplianceAggregate::fake();
        $reportType = 'SAR';
        $data = [
            'period' => '2024-Q1',
            'total_transactions' => 15000,
            'flagged_transactions' => 23,
        ];

        $aggregate->generateRegulatoryReport($reportType, $data);

        $aggregate->assertRecorded([
            new RegulatoryReportGenerated($reportType, $data),
        ]);
    }

    public function test_applies_kyc_submission_received_event(): void
    {
        $aggregate = new ComplianceAggregate();
        $userUuid = 'user-apply-1';
        $documents = [['type' => 'passport']];

        $event = new KycSubmissionReceived($userUuid, $documents);
        $aggregate->applyKycSubmissionReceived($event);

        // Use reflection to check private properties
        $reflection = new \ReflectionClass($aggregate);
        $statusProperty = $reflection->getProperty('kycStatus');
        $statusProperty->setAccessible(true);
        
        $this->assertEquals('pending', $statusProperty->getValue($aggregate));
    }

    public function test_applies_kyc_verification_completed_event(): void
    {
        $aggregate = new ComplianceAggregate();
        $userUuid = 'user-verify-1';
        $level = 'full';

        $event = new KycVerificationCompleted($userUuid, $level);
        $aggregate->applyKycVerificationCompleted($event);

        $reflection = new \ReflectionClass($aggregate);
        
        $statusProperty = $reflection->getProperty('kycStatus');
        $statusProperty->setAccessible(true);
        $this->assertEquals('approved', $statusProperty->getValue($aggregate));
        
        $levelProperty = $reflection->getProperty('kycLevel');
        $levelProperty->setAccessible(true);
        $this->assertEquals('full', $levelProperty->getValue($aggregate));
    }

    public function test_applies_kyc_verification_rejected_event(): void
    {
        $aggregate = new ComplianceAggregate();
        $userUuid = 'user-reject-1';
        $reason = 'Documents expired';

        $event = new KycVerificationRejected($userUuid, $reason);
        $aggregate->applyKycVerificationRejected($event);

        $reflection = new \ReflectionClass($aggregate);
        $statusProperty = $reflection->getProperty('kycStatus');
        $statusProperty->setAccessible(true);
        
        $this->assertEquals('rejected', $statusProperty->getValue($aggregate));
    }

    public function test_full_kyc_workflow(): void
    {
        $aggregate = ComplianceAggregate::fake();
        $userUuid = 'user-workflow-1';

        // Submit KYC
        $documents = [
            ['type' => 'passport', 'filename' => 'passport.jpg'],
            ['type' => 'driver_license', 'filename' => 'license.jpg'],
        ];
        $aggregate->submitKyc($userUuid, $documents);

        // Approve KYC
        $aggregate->approveKyc($userUuid, 'enhanced');

        // Verify all events were recorded in order
        $aggregate->assertRecordedCount(4); // 1 submission + 2 documents + 1 approval
    }

    public function test_full_gdpr_workflow(): void
    {
        $aggregate = ComplianceAggregate::fake();
        $userUuid = 'user-gdpr-workflow';

        // Request export
        $aggregate->requestGdprExport($userUuid, ['format' => 'json']);
        
        // Complete export
        $aggregate->completeGdprExport($userUuid, '/exports/data.json');
        
        // Request deletion
        $aggregate->requestGdprDeletion($userUuid, 'User request');
        
        // Complete deletion
        $aggregate->completeGdprDeletion($userUuid);

        $aggregate->assertRecordedCount(4);
    }
}