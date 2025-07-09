<?php

namespace Tests\Unit\Domain\Compliance\Services;

use App\Domain\Compliance\Events\ScreeningCompleted;
use App\Domain\Compliance\Events\ScreeningMatchFound;
use App\Domain\Compliance\Services\AmlScreeningService;
use App\Models\AmlScreening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AmlScreeningServiceTest extends TestCase
{
    use RefreshDatabase;

    private AmlScreeningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AmlScreeningService();
        Event::fake();
    }

    public function test_perform_comprehensive_screening_creates_screening_record(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $user = User::factory()->create([
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $screening = $this->service->performComprehensiveScreening($user);

        $this->assertInstanceOf(AmlScreening::class, $screening);
        $this->assertEquals(AmlScreening::TYPE_COMPREHENSIVE, $screening->type);
        $this->assertEquals(AmlScreening::STATUS_COMPLETED, $screening->status);
        $this->assertEquals($user->uuid, $screening->entity_id);
        $this->assertEquals(User::class, $screening->entity_type);
    }

    public function test_perform_comprehensive_screening_triggers_events(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $user = User::factory()->create();

        $screening = $this->service->performComprehensiveScreening($user);

        Event::assertDispatched(ScreeningCompleted::class, function ($event) use ($screening) {
            return $event->screening->id === $screening->id;
        });
    }

    public function test_perform_sanctions_screening_checks_multiple_lists(): void
    {
        Http::fake([
            'api.ofac.treasury.gov/*' => Http::response(['results' => []], 200),
            'webgate.ec.europa.eu/*'  => Http::response(['results' => []], 200),
            'api.un.org/*'            => Http::response(['results' => []], 200),
        ]);

        $screening = AmlScreening::factory()->create([
            'search_parameters' => ['name' => 'Test User'],
        ]);

        $results = $this->service->performSanctionsScreening($screening);

        $this->assertArrayHasKey('matches', $results);
        $this->assertArrayHasKey('lists_checked', $results);
        $this->assertArrayHasKey('total_matches', $results);
        $this->assertEquals(0, $results['total_matches']);
    }

    public function test_perform_screening_with_matches_triggers_match_event(): void
    {
        // Mock API to return matches
        Http::fake([
            '*' => Http::response([
                'results' => [
                    ['name' => 'John Doe', 'score' => 0.95],
                ],
            ], 200),
        ]);

        $user = User::factory()->create(['name' => 'John Doe']);

        // Mock the service to return matches
        $this->service = $this->getMockBuilder(AmlScreeningService::class)
            ->onlyMethods(['performSanctionsScreening', 'performPEPScreening', 'performAdverseMediaScreening'])
            ->getMock();

        $matchResults = [
            'matches'       => [['name' => 'John Doe', 'score' => 0.95]],
            'lists_checked' => ['OFAC'],
            'total_matches' => 1,
        ];

        $this->service->method('performSanctionsScreening')->willReturn($matchResults);
        $this->service->method('performPEPScreening')->willReturn(['matches' => [], 'total_matches' => 0]);
        $this->service->method('performAdverseMediaScreening')->willReturn(['matches' => [], 'total_matches' => 0]);

        $screening = $this->service->performComprehensiveScreening($user);

        Event::assertDispatched(ScreeningMatchFound::class, function ($event) use ($screening) {
            return $event->screening->id === $screening->id;
        });
    }

    public function test_screening_fails_gracefully_on_api_error(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();

        $this->expectException(\Exception::class);

        $screening = $this->service->performComprehensiveScreening($user);

        // Check that screening was marked as failed
        $this->assertEquals(AmlScreening::STATUS_FAILED, $screening->fresh()->status);
    }

    public function test_calculate_overall_risk_with_no_matches(): void
    {
        $sanctionsResults = ['total_matches' => 0];
        $pepResults = ['total_matches' => 0];
        $adverseMediaResults = ['total_matches' => 0];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateOverallRisk');
        $method->setAccessible(true);

        $risk = $method->invoke($this->service, $sanctionsResults, $pepResults, $adverseMediaResults);

        $this->assertEquals('low', $risk);
    }

    public function test_calculate_overall_risk_with_sanctions_match(): void
    {
        $sanctionsResults = ['total_matches' => 1];
        $pepResults = ['total_matches' => 0];
        $adverseMediaResults = ['total_matches' => 0];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateOverallRisk');
        $method->setAccessible(true);

        $risk = $method->invoke($this->service, $sanctionsResults, $pepResults, $adverseMediaResults);

        $this->assertEquals('critical', $risk);
    }

    public function test_count_total_matches(): void
    {
        $sanctionsResults = ['total_matches' => 2];
        $pepResults = ['total_matches' => 1];
        $adverseMediaResults = ['total_matches' => 3];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('countTotalMatches');
        $method->setAccessible(true);

        $total = $method->invoke($this->service, $sanctionsResults, $pepResults, $adverseMediaResults);

        $this->assertEquals(6, $total);
    }

    public function test_perform_pep_screening(): void
    {
        Http::fake([
            '*' => Http::response(['results' => []], 200),
        ]);

        $screening = AmlScreening::factory()->create([
            'search_parameters' => ['name' => 'Test User'],
        ]);

        $results = $this->service->performPEPScreening($screening);

        $this->assertArrayHasKey('matches', $results);
        $this->assertArrayHasKey('total_matches', $results);
        $this->assertIsArray($results['matches']);
    }

    public function test_perform_adverse_media_screening(): void
    {
        Http::fake([
            '*' => Http::response(['articles' => []], 200),
        ]);

        $screening = AmlScreening::factory()->create([
            'search_parameters' => ['name' => 'Test User'],
        ]);

        $results = $this->service->performAdverseMediaScreening($screening);

        $this->assertArrayHasKey('matches', $results);
        $this->assertArrayHasKey('total_matches', $results);
        $this->assertIsArray($results['matches']);
    }

    public function test_screening_with_custom_parameters(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $user = User::factory()->create();
        $parameters = [
            'include_aliases' => true,
            'fuzzy_matching'  => true,
            'threshold'       => 0.8,
        ];

        $screening = $this->service->performComprehensiveScreening($user, $parameters);

        $this->assertEquals($parameters['threshold'], $screening->search_parameters['threshold']);
        $this->assertTrue($screening->search_parameters['include_aliases']);
    }

    public function test_screening_updates_status_during_process(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $user = User::factory()->create();

        // Track status changes
        $statusChanges = [];

        AmlScreening::created(function ($screening) use (&$statusChanges) {
            $statusChanges[] = $screening->status;
        });

        AmlScreening::updated(function ($screening) use (&$statusChanges) {
            $statusChanges[] = $screening->status;
        });

        $this->service->performComprehensiveScreening($user);

        $this->assertContains(AmlScreening::STATUS_IN_PROGRESS, $statusChanges);
        $this->assertContains(AmlScreening::STATUS_COMPLETED, $statusChanges);
    }
}
