<?php

namespace Tests\Unit\Domain\Compliance\Services;

use App\Domain\Compliance\Services\KycService;
use App\Models\AuditLog;
use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycServiceTest extends TestCase
{
    use RefreshDatabase;

    private KycService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KycService();
        Storage::fake('private');
    }

    public function test_submit_kyc_updates_user_status(): void
    {
        $user = User::factory()->create([
            'kyc_status' => null,
            'kyc_submitted_at' => null,
        ]);

        $documents = [
            [
                'type' => 'passport',
                'file' => UploadedFile::fake()->image('passport.jpg', 1000, 1000),
            ],
            [
                'type' => 'proof_of_address',
                'file' => UploadedFile::fake()->create('utility_bill.pdf', 500),
            ],
        ];

        $this->service->submitKyc($user, $documents);

        $user->refresh();
        $this->assertEquals('pending', $user->kyc_status);
        $this->assertNotNull($user->kyc_submitted_at);
    }

    public function test_submit_kyc_stores_documents(): void
    {
        $user = User::factory()->create();

        $documents = [
            [
                'type' => 'passport',
                'file' => UploadedFile::fake()->image('passport.jpg'),
            ],
            [
                'type' => 'drivers_license',
                'file' => UploadedFile::fake()->image('license.jpg'),
            ],
        ];

        $this->service->submitKyc($user, $documents);

        // Check documents were created
        $storedDocs = KycDocument::where('user_uuid', $user->uuid)->get();
        $this->assertCount(2, $storedDocs);
        
        // Check document types
        $this->assertTrue($storedDocs->contains('document_type', 'passport'));
        $this->assertTrue($storedDocs->contains('document_type', 'drivers_license'));
        
        // Check files were stored
        foreach ($storedDocs as $doc) {
            Storage::disk('private')->assertExists($doc->file_path);
        }
    }

    public function test_submit_kyc_creates_audit_log(): void
    {
        $user = User::factory()->create();

        $documents = [
            [
                'type' => 'passport',
                'file' => UploadedFile::fake()->image('passport.jpg'),
            ],
        ];

        $this->service->submitKyc($user, $documents);

        $auditLog = AuditLog::where('user_uuid', $user->uuid)
            ->where('action', 'kyc.submitted')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals(1, $auditLog->new_values['documents']);
        $this->assertContains('passport', $auditLog->metadata['document_types']);
        $this->assertEquals('kyc,compliance', $auditLog->tags);
    }

    public function test_verify_kyc_approves_user(): void
    {
        $user = User::factory()->create([
            'kyc_status' => 'pending',
            'kyc_approved_at' => null,
        ]);

        $this->service->verifyKyc($user, 'admin-123', ['notes' => 'All documents verified']);

        $user->refresh();
        $this->assertEquals('approved', $user->kyc_status);
        $this->assertNotNull($user->kyc_approved_at);
    }

    public function test_verify_kyc_creates_audit_log(): void
    {
        $user = User::factory()->create(['kyc_status' => 'pending']);

        $this->service->verifyKyc($user, 'admin-456', ['notes' => 'Verified']);

        $auditLog = AuditLog::where('user_uuid', $user->uuid)
            ->where('action', 'kyc.approved')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('pending', $auditLog->old_values['kyc_status']);
        $this->assertEquals('approved', $auditLog->new_values['kyc_status']);
        $this->assertEquals('admin-456', $auditLog->metadata['verified_by']);
    }

    public function test_reject_kyc_updates_status(): void
    {
        $user = User::factory()->create(['kyc_status' => 'pending']);

        $this->service->rejectKyc($user, 'admin-789', 'Invalid documents');

        $user->refresh();
        $this->assertEquals('rejected', $user->kyc_status);
        $this->assertNotNull($user->kyc_rejected_at);
    }

    public function test_get_kyc_status_returns_user_status(): void
    {
        $user = User::factory()->create(['kyc_status' => 'approved']);
        
        $status = $this->service->getKycStatus($user);
        
        $this->assertEquals('approved', $status);
    }

    public function test_is_kyc_approved_returns_correct_boolean(): void
    {
        $approvedUser = User::factory()->create(['kyc_status' => 'approved']);
        $pendingUser = User::factory()->create(['kyc_status' => 'pending']);
        $rejectedUser = User::factory()->create(['kyc_status' => 'rejected']);

        $this->assertTrue($this->service->isKycApproved($approvedUser));
        $this->assertFalse($this->service->isKycApproved($pendingUser));
        $this->assertFalse($this->service->isKycApproved($rejectedUser));
    }

    public function test_store_document_creates_hash(): void
    {
        $user = User::factory()->create();
        
        $file = UploadedFile::fake()->image('test.jpg');
        $document = [
            'type' => 'passport',
            'file' => $file,
        ];

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('storeDocument');
        $method->setAccessible(true);
        
        $kycDocument = $method->invoke($this->service, $user, $document);

        $this->assertNotNull($kycDocument->file_hash);
        $this->assertEquals(64, strlen($kycDocument->file_hash)); // SHA-256 hash length
        $this->assertEquals('passport', $kycDocument->document_type);
        $this->assertEquals('test.jpg', $kycDocument->metadata['original_name']);
    }

    public function test_handle_document_with_different_types(): void
    {
        $user = User::factory()->create();

        $documentTypes = [
            'passport',
            'drivers_license',
            'national_id',
            'proof_of_address',
            'bank_statement',
            'tax_document',
        ];

        foreach ($documentTypes as $type) {
            $documents = [[
                'type' => $type,
                'file' => UploadedFile::fake()->create("{$type}.pdf", 100),
            ]];

            $this->service->submitKyc($user, $documents);
        }

        $storedDocs = KycDocument::where('user_uuid', $user->uuid)->pluck('document_type');
        
        foreach ($documentTypes as $type) {
            $this->assertContains($type, $storedDocs);
        }
    }
}