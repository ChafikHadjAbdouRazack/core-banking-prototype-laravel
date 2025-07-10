<?php

namespace Tests\Unit\Domain\Fraud\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\ServiceTestCase;

class FraudDetectionServiceTest extends ServiceTestCase
{
    use RefreshDatabase;

    private FraudDetectionService $service;

    private RuleEngineService $ruleEngine;

    private BehavioralAnalysisService $behavioralAnalysis;

    private DeviceFingerprintService $deviceService;

    private MachineLearningService $mlService;

    private FraudCaseService $caseService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ruleEngine = Mockery::mock(RuleEngineService::class);
        $this->behavioralAnalysis = Mockery::mock(BehavioralAnalysisService::class);
        $this->deviceService = Mockery::mock(DeviceFingerprintService::class);
        $this->mlService = Mockery::mock(MachineLearningService::class);
        $this->caseService = Mockery::mock(FraudCaseService::class);

        $this->service = new FraudDetectionService(
            $this->ruleEngine,
            $this->behavioralAnalysis,
            $this->deviceService,
            $this->mlService,
            $this->caseService
        );

        Event::fake();
    }

    #[Test]
    public function test_analyze_transaction_creates_fraud_score(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_uuid' => $user->uuid]);
        $transaction = Transaction::factory()->create([
            'account_id' => $account->id,
            'amount'     => 10000,
        ]);

        // Mock service responses
        $this->mockServicesForLowRisk();

        $fraudScore = $this->service->analyzeTransaction($transaction);

        $this->assertInstanceOf(FraudScore::class, $fraudScore);
        $this->assertEquals($transaction->id, $fraudScore->entity_id);
        $this->assertEquals(Transaction::class, $fraudScore->entity_type);
        $this->assertEquals(FraudScore::SCORE_TYPE_REAL_TIME, $fraudScore->score_type);
    }

    #[Test]
    public function test_analyze_transaction_with_low_risk_passes(): void
    {
        $transaction = $this->createTransaction();

        $this->mockServicesForLowRisk();

        $fraudScore = $this->service->analyzeTransaction($transaction);

        $this->assertLessThan(30, $fraudScore->score);
        $this->assertEquals('pass', $fraudScore->action);
        $this->assertEquals('low', $fraudScore->risk_level);

        Event::assertNotDispatched(FraudDetected::class);
        Event::assertNotDispatched(TransactionBlocked::class);
    }

    #[Test]
    public function test_analyze_transaction_with_medium_risk_requires_challenge(): void
    {
        $transaction = $this->createTransaction();

        $this->mockServicesForMediumRisk();

        $fraudScore = $this->service->analyzeTransaction($transaction);

        $this->assertBetween(30, 70, $fraudScore->score);
        $this->assertEquals('challenge', $fraudScore->action);
        $this->assertEquals('medium', $fraudScore->risk_level);

        Event::assertDispatched(ChallengeRequired::class);
        Event::assertNotDispatched(TransactionBlocked::class);
    }

    #[Test]
    public function test_analyze_transaction_with_high_risk_blocks_transaction(): void
    {
        $transaction = $this->createTransaction();

        $this->mockServicesForHighRisk();

        $fraudScore = $this->service->analyzeTransaction($transaction);

        $this->assertGreaterThan(70, $fraudScore->score);
        $this->assertEquals('block', $fraudScore->action);
        $this->assertEquals('high', $fraudScore->risk_level);

        Event::assertDispatched(FraudDetected::class);
        Event::assertDispatched(TransactionBlocked::class);
    }

    #[Test]
    public function test_analyze_transaction_with_ml_enabled(): void
    {
        $transaction = $this->createTransaction();

        $this->mockServicesForLowRisk();
        $this->mlService->shouldReceive('isEnabled')->andReturn(true);
        $this->mlService->shouldReceive('predict')
            ->once()
            ->andReturn(['score' => 0.15, 'confidence' => 0.95]);

        $fraudScore = $this->service->analyzeTransaction($transaction);

        $this->assertArrayHasKey('ml_prediction', $fraudScore->analysis_results);
        $this->assertEquals(0.15, $fraudScore->analysis_results['ml_prediction']['score']);
    }

    #[Test]
    public function test_analyze_transaction_handles_device_data(): void
    {
        $transaction = $this->createTransaction();
        $deviceData = [
            'fingerprint' => 'device123',
            'ip'          => '192.168.1.1',
            'user_agent'  => 'Mozilla/5.0',
        ];

        $this->mockServicesForLowRisk();
        $this->deviceService->shouldReceive('analyzeDevice')
            ->with(Mockery::on(function ($data) use ($deviceData) {
                return $data['fingerprint'] === $deviceData['fingerprint'];
            }))
            ->andReturn(['risk_score' => 10, 'is_known' => true]);

        $fraudScore = $this->service->analyzeTransaction($transaction, ['device_data' => $deviceData]);

        $this->assertArrayHasKey('device_analysis', $fraudScore->analysis_results);
        $this->assertTrue($fraudScore->analysis_results['device_analysis']['is_known']);
    }

    #[Test]
    public function test_analyze_user_activity_for_historical_analysis(): void
    {
        $user = User::factory()->create();
        $startDate = now()->subDays(30);
        $endDate = now();

        // Mock transaction history
        $this->behavioralAnalysis->shouldReceive('getHistoricalBehavior')
            ->with($user, $startDate, $endDate)
            ->andReturn([
                'avg_transaction_amount' => 5000,
                'transaction_count'      => 25,
                'unusual_patterns'       => [],
            ]);

        $analysis = $this->service->analyzeUserActivity($user, $startDate, $endDate);

        $this->assertArrayHasKey('behavioral_analysis', $analysis);
        $this->assertArrayHasKey('risk_indicators', $analysis);
        $this->assertArrayHasKey('recommendations', $analysis);
    }

    #[Test]
    public function test_recalculate_score_updates_existing_score(): void
    {
        $transaction = $this->createTransaction();
        $fraudScore = FraudScore::factory()->create([
            'entity_id'   => $transaction->id,
            'entity_type' => Transaction::class,
            'score'       => 25,
            'action'      => 'pass',
        ]);

        $this->mockServicesForMediumRisk();

        $updatedScore = $this->service->recalculateScore($fraudScore);

        $this->assertGreaterThan(25, $updatedScore->score);
        $this->assertEquals('challenge', $updatedScore->action);
        $this->assertArrayHasKey('recalculation_reason', $updatedScore->metadata);
    }

    #[Test]
    public function test_get_fraud_indicators_returns_risk_factors(): void
    {
        $transaction = $this->createTransaction(['amount' => 50000]);

        $indicators = $this->service->getFraudIndicators($transaction);

        $this->assertIsArray($indicators);
        $this->assertArrayHasKey('transaction_indicators', $indicators);
        $this->assertArrayHasKey('user_indicators', $indicators);
        $this->assertArrayHasKey('contextual_indicators', $indicators);
    }

    // Helper methods
    private function createTransaction(array $attributes = []): Transaction
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_uuid' => $user->uuid]);

        return Transaction::factory()->create(array_merge([
            'account_id' => $account->id,
            'amount'     => 10000,
            'type'       => 'transfer',
        ], $attributes));
    }

    private function mockServicesForLowRisk(): void
    {
        $this->ruleEngine->shouldReceive('evaluate')
            ->andReturn(['score' => 10, 'triggered_rules' => []]);

        $this->behavioralAnalysis->shouldReceive('analyze')
            ->andReturn(['score' => 5, 'anomalies' => []]);

        $this->deviceService->shouldReceive('analyzeDevice')
            ->andReturn(['risk_score' => 5, 'is_known' => true]);

        $this->mlService->shouldReceive('isEnabled')->andReturn(false);
    }

    private function mockServicesForMediumRisk(): void
    {
        $this->ruleEngine->shouldReceive('evaluate')
            ->andReturn(['score' => 25, 'triggered_rules' => ['unusual_amount']]);

        $this->behavioralAnalysis->shouldReceive('analyze')
            ->andReturn(['score' => 20, 'anomalies' => ['time_pattern']]);

        $this->deviceService->shouldReceive('analyzeDevice')
            ->andReturn(['risk_score' => 15, 'is_known' => false]);

        $this->mlService->shouldReceive('isEnabled')->andReturn(false);
    }

    private function mockServicesForHighRisk(): void
    {
        $this->ruleEngine->shouldReceive('evaluate')
            ->andReturn(['score' => 50, 'triggered_rules' => ['blacklist_match', 'velocity_check']]);

        $this->behavioralAnalysis->shouldReceive('analyze')
            ->andReturn(['score' => 40, 'anomalies' => ['location_jump', 'unusual_merchant']]);

        $this->deviceService->shouldReceive('analyzeDevice')
            ->andReturn(['risk_score' => 30, 'is_known' => false, 'is_vpn' => true]);

        $this->mlService->shouldReceive('isEnabled')->andReturn(false);

        $this->caseService->shouldReceive('createCase')->once();
    }

    private function assertBetween($min, $max, $value): void
    {
        $this->assertGreaterThanOrEqual($min, $value);
        $this->assertLessThanOrEqual($max, $value);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
