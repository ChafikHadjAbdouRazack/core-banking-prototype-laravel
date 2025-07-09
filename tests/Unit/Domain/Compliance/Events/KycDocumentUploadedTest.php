<?php

namespace Tests\Unit\Domain\Compliance\Events;

use App\Domain\Compliance\Events\KycDocumentUploaded;
use Tests\TestCase;

class KycDocumentUploadedTest extends TestCase
{
    public function test_creates_event_with_user_uuid_and_document(): void
    {
        $userUuid = 'user-123-uuid';
        $document = [
            'type'        => 'passport',
            'filename'    => 'passport.jpg',
            'size'        => 1024000,
            'mime_type'   => 'image/jpeg',
            'uploaded_at' => '2024-01-15T10:30:00Z',
        ];

        $event = new KycDocumentUploaded($userUuid, $document);

        $this->assertEquals($userUuid, $event->userUuid);
        $this->assertEquals($document, $event->document);
        $this->assertEquals('passport', $event->document['type']);
    }

    public function test_handles_different_document_types(): void
    {
        $documentTypes = [
            'passport'         => ['number' => 'AB123456', 'country' => 'US'],
            'driver_license'   => ['number' => 'DL789012', 'state' => 'CA'],
            'national_id'      => ['number' => 'ID345678', 'issued_by' => 'Government'],
            'proof_of_address' => ['type' => 'utility_bill', 'date' => '2024-01-01'],
            'bank_statement'   => ['bank' => 'Test Bank', 'period' => '2024-01'],
        ];

        foreach ($documentTypes as $type => $metadata) {
            $document = array_merge(
                ['type' => $type],
                $metadata
            );

            $event = new KycDocumentUploaded('user-uuid', $document);

            $this->assertEquals($type, $event->document['type']);
            foreach ($metadata as $key => $value) {
                $this->assertEquals($value, $event->document[$key]);
            }
        }
    }

    public function test_event_extends_should_be_stored(): void
    {
        $event = new KycDocumentUploaded('user-uuid', ['type' => 'test']);

        $this->assertInstanceOf(\Spatie\EventSourcing\StoredEvents\ShouldBeStored::class, $event);
    }

    public function test_handles_document_with_validation_results(): void
    {
        $document = [
            'type'       => 'passport',
            'filename'   => 'passport_scan.pdf',
            'validation' => [
                'ocr_performed'  => true,
                'data_extracted' => [
                    'name'            => 'John Doe',
                    'passport_number' => 'AB123456',
                    'expiry_date'     => '2025-12-31',
                ],
                'verification_score' => 0.95,
                'flags'              => [],
            ],
        ];

        $event = new KycDocumentUploaded('user-456', $document);

        $this->assertTrue($event->document['validation']['ocr_performed']);
        $this->assertEquals(0.95, $event->document['validation']['verification_score']);
        $this->assertEquals('John Doe', $event->document['validation']['data_extracted']['name']);
    }

    public function test_handles_document_with_security_checks(): void
    {
        $document = [
            'type'            => 'driver_license',
            'security_checks' => [
                'tamper_detection'   => 'passed',
                'authenticity_check' => 'passed',
                'expiry_check'       => 'valid',
                'watermark_verified' => true,
                'hologram_present'   => true,
            ],
        ];

        $event = new KycDocumentUploaded('user-789', $document);

        $this->assertEquals('passed', $event->document['security_checks']['tamper_detection']);
        $this->assertTrue($event->document['security_checks']['watermark_verified']);
    }

    public function test_handles_minimal_document_data(): void
    {
        $minimalDocument = [
            'type' => 'other',
        ];

        $event = new KycDocumentUploaded('user-minimal', $minimalDocument);

        $this->assertEquals('other', $event->document['type']);
        $this->assertCount(1, $event->document);
    }

    public function test_handles_document_with_processing_metadata(): void
    {
        $document = [
            'type'       => 'bank_statement',
            'processing' => [
                'upload_timestamp'       => 1705320600,
                'processing_duration_ms' => 2500,
                'storage_location'       => 's3://kyc-docs/user-123/bank_statement.pdf',
                'encryption_applied'     => true,
                'hash'                   => 'sha256:abcdef123456...',
            ],
        ];

        $event = new KycDocumentUploaded('user-processing', $document);

        $this->assertEquals(2500, $event->document['processing']['processing_duration_ms']);
        $this->assertTrue($event->document['processing']['encryption_applied']);
        $this->assertStringStartsWith('sha256:', $event->document['processing']['hash']);
    }
}
