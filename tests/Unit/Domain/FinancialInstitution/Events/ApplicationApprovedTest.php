<?php

namespace Tests\Unit\Domain\FinancialInstitution\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\DomainTestCase;

class ApplicationApprovedTest extends DomainTestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_creates_event_with_application_and_partner(): void
    {
        $application = FinancialInstitutionApplication::factory()->create([
            'institution_name' => 'Approved Bank',
            'status' => 'approved',
        ]);

        $partner = FinancialInstitutionPartner::factory()->create([
            'application_id' => $application->id,
            'name' => 'Approved Bank',
            'status' => 'active',
        ]);

        $event = new ApplicationApproved($application, $partner);

        $this->assertSame($application->id, $event->application->id);
        $this->assertSame($partner->id, $event->partner->id);
        $this->assertEquals('Approved Bank', $event->application->institution_name);
        $this->assertEquals('active', $event->partner->status);
    }

    #[Test]
    public function test_event_uses_required_traits(): void
    {
        $application = FinancialInstitutionApplication::factory()->create();
        $partner = FinancialInstitutionPartner::factory()->create();

        $event = new ApplicationApproved($application, $partner);

        $traits = class_uses($event);

        $this->assertArrayHasKey('Illuminate\Foundation\Events\Dispatchable', $traits);
        $this->assertArrayHasKey('Illuminate\Broadcasting\InteractsWithSockets', $traits);
        $this->assertArrayHasKey('Illuminate\Queue\SerializesModels', $traits);
    }

    #[Test]
    public function test_event_properties_are_readonly(): void
    {
        $application = FinancialInstitutionApplication::factory()->create();
        $partner = FinancialInstitutionPartner::factory()->create();

        $event = new ApplicationApproved($application, $partner);

        // Properties are readonly, attempting to modify should cause error
        $this->expectException(\Error::class);
        $this->expectExceptionMessageMatches('/Cannot modify readonly property/');
        $event->application = FinancialInstitutionApplication::factory()->create();
    }

    #[Test]
    public function test_event_serializes_correctly(): void
    {
        $application = FinancialInstitutionApplication::factory()->create([
            'institution_name' => 'Serialized Bank',
            'registration_number' => 'SER123456',
        ]);

        $partner = FinancialInstitutionPartner::factory()->create([
            'application_id' => $application->id,
            'partner_code' => 'PARTNER001',
        ]);

        $event = new ApplicationApproved($application, $partner);

        // Serialize and unserialize
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertEquals($application->id, $unserialized->application->id);
        $this->assertEquals('Serialized Bank', $unserialized->application->institution_name);
        $this->assertEquals($partner->id, $unserialized->partner->id);
        $this->assertEquals('PARTNER001', $unserialized->partner->partner_code);
    }

    #[Test]
    public function test_can_be_dispatched_as_event(): void
    {
        $application = FinancialInstitutionApplication::factory()->create();
        $partner = FinancialInstitutionPartner::factory()->create();

        $this->expectsEvents(ApplicationApproved::class);

        event(new ApplicationApproved($application, $partner));
    }
}
