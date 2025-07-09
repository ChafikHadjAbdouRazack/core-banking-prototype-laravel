<?php

namespace Tests\Unit\Domain\Compliance\Services;

use App\Domain\Compliance\Services\CustomerRiskService;
use App\Domain\Compliance\Services\SuspiciousActivityReportService;
use App\Domain\Compliance\Services\TransactionMonitoringService;
use App\Models\CustomerRiskProfile;
use App\Models\Transaction;
use App\Models\TransactionMonitoringRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class TransactionMonitoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionMonitoringService $service;
    private SuspiciousActivityReportService $sarService;
    private CustomerRiskService $riskService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sarService = Mockery::mock(SuspiciousActivityReportService::class);
        $this->riskService = Mockery::mock(CustomerRiskService::class);
        
        $this->service = new TransactionMonitoringService(
            $this->sarService,
            $this->riskService
        );
    }

    public function test_monitor_transaction_passes_when_no_alerts(): void
    {
        $transaction = Transaction::factory()->create([
            'amount' => 1000,
            'type' => 'transfer',
        ]);

        // Mock risk profile
        $riskProfile = new CustomerRiskProfile();
        $riskProfile->risk_level = 'low';
        
        $this->mockGetCustomerRiskProfile($transaction, $riskProfile);
        $this->mockGetApplicableRules($transaction, $riskProfile, collect());

        $result = $this->service->monitorTransaction($transaction);

        $this->assertTrue($result['passed']);
        $this->assertEmpty($result['alerts']);
        $this->assertEmpty($result['actions']);
    }

    public function test_monitor_transaction_creates_alerts_for_triggered_rules(): void
    {
        $transaction = Transaction::factory()->create([
            'amount' => 100000, // Large amount
        ]);

        $riskProfile = new CustomerRiskProfile();
        $riskProfile->risk_level = 'medium';

        // Create mock rule
        $rule = Mockery::mock(TransactionMonitoringRule::class);
        $rule->shouldReceive('getActions')->andReturn([TransactionMonitoringRule::ACTION_REVIEW]);
        $rule->shouldReceive('recordTrigger')->once();
        $rule->name = 'Large Transaction Rule';
        $rule->id = 1;
        
        $this->mockGetCustomerRiskProfile($transaction, $riskProfile);
        $this->mockGetApplicableRules($transaction, $riskProfile, collect([$rule]));
        $this->mockEvaluateRule($rule, $transaction, $riskProfile, true);

        $result = $this->service->monitorTransaction($transaction);

        $this->assertTrue($result['passed']);
        $this->assertCount(1, $result['alerts']);
        $this->assertContains(TransactionMonitoringRule::ACTION_REVIEW, $result['actions']);
    }

    public function test_monitor_transaction_blocks_when_block_action_triggered(): void
    {
        $transaction = Transaction::factory()->create([
            'amount' => 500000,
        ]);

        $riskProfile = new CustomerRiskProfile();
        $riskProfile->risk_level = 'high';

        $rule = Mockery::mock(TransactionMonitoringRule::class);
        $rule->shouldReceive('getActions')->andReturn([TransactionMonitoringRule::ACTION_BLOCK]);
        $rule->shouldReceive('recordTrigger')->once();
        $rule->name = 'High Risk Block Rule';
        $rule->id = 2;
        
        $this->mockGetCustomerRiskProfile($transaction, $riskProfile);
        $this->mockGetApplicableRules($transaction, $riskProfile, collect([$rule]));
        $this->mockEvaluateRule($rule, $transaction, $riskProfile, true);

        $result = $this->service->monitorTransaction($transaction);

        $this->assertFalse($result['passed']);
        $this->assertNotEmpty($result['alerts']);
        $this->assertContains(TransactionMonitoringRule::ACTION_BLOCK, $result['actions']);
    }

    public function test_monitor_transaction_handles_multiple_rules(): void
    {
        $transaction = Transaction::factory()->create();
        $riskProfile = new CustomerRiskProfile();

        $rule1 = $this->createMockRule(1, 'Rule 1', [TransactionMonitoringRule::ACTION_REVIEW]);
        $rule2 = $this->createMockRule(2, 'Rule 2', [TransactionMonitoringRule::ACTION_REPORT]);
        $rule3 = $this->createMockRule(3, 'Rule 3', [TransactionMonitoringRule::ACTION_REVIEW]); // Won't trigger

        $this->mockGetCustomerRiskProfile($transaction, $riskProfile);
        $this->mockGetApplicableRules($transaction, $riskProfile, collect([$rule1, $rule2, $rule3]));
        
        // Only first two rules trigger
        $this->mockEvaluateRule($rule1, $transaction, $riskProfile, true);
        $this->mockEvaluateRule($rule2, $transaction, $riskProfile, true);
        $this->mockEvaluateRule($rule3, $transaction, $riskProfile, false);

        $result = $this->service->monitorTransaction($transaction);

        $this->assertCount(2, $result['alerts']);
        $this->assertContains(TransactionMonitoringRule::ACTION_REVIEW, $result['actions']);
        $this->assertContains(TransactionMonitoringRule::ACTION_REPORT, $result['actions']);
    }

    public function test_monitor_transaction_handles_exceptions_gracefully(): void
    {
        $transaction = Transaction::factory()->create();

        // Mock exception during risk profile fetch
        $this->service = Mockery::mock(TransactionMonitoringService::class)->makePartial();
        $this->service->shouldReceive('getCustomerRiskProfile')
            ->andThrow(new \Exception('Database error'));

        Log::shouldReceive('error')->once();

        $result = $this->service->monitorTransaction($transaction);

        $this->assertTrue($result['passed']); // Fail-safe allows transaction
        $this->assertCount(1, $result['alerts']);
        $this->assertEquals('system_error', $result['alerts'][0]['type']);
        $this->assertContains(TransactionMonitoringRule::ACTION_REVIEW, $result['actions']);
    }

    public function test_monitor_transaction_updates_behavioral_risk_when_alerts_exist(): void
    {
        $transaction = Transaction::factory()->create();
        $riskProfile = new CustomerRiskProfile();

        $rule = $this->createMockRule(1, 'Suspicious Pattern', [TransactionMonitoringRule::ACTION_REVIEW]);
        
        $this->mockGetCustomerRiskProfile($transaction, $riskProfile);
        $this->mockGetApplicableRules($transaction, $riskProfile, collect([$rule]));
        $this->mockEvaluateRule($rule, $transaction, $riskProfile, true);

        // Expect behavioral risk update
        $this->service = Mockery::mock(TransactionMonitoringService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
            
        $this->service->shouldReceive('getCustomerRiskProfile')->andReturn($riskProfile);
        $this->service->shouldReceive('getApplicableRules')->andReturn(collect([$rule]));
        $this->service->shouldReceive('evaluateRule')->andReturn(true);
        $this->service->shouldReceive('createAlert')->andReturn(['type' => 'rule_trigger']);
        $this->service->shouldReceive('processActions')->once();
        $this->service->shouldReceive('updateBehavioralRisk')->once();

        $result = $this->service->monitorTransaction($transaction);

        $this->assertNotEmpty($result['alerts']);
    }

    public function test_monitor_transaction_deduplicates_actions(): void
    {
        $transaction = Transaction::factory()->create();
        $riskProfile = new CustomerRiskProfile();

        // Multiple rules with same actions
        $rule1 = $this->createMockRule(1, 'Rule 1', [TransactionMonitoringRule::ACTION_REVIEW, TransactionMonitoringRule::ACTION_REPORT]);
        $rule2 = $this->createMockRule(2, 'Rule 2', [TransactionMonitoringRule::ACTION_REVIEW]);

        $this->mockGetCustomerRiskProfile($transaction, $riskProfile);
        $this->mockGetApplicableRules($transaction, $riskProfile, collect([$rule1, $rule2]));
        $this->mockEvaluateRule($rule1, $transaction, $riskProfile, true);
        $this->mockEvaluateRule($rule2, $transaction, $riskProfile, true);

        $result = $this->service->monitorTransaction($transaction);

        // Should only have unique actions
        $uniqueActions = array_unique($result['actions']);
        $this->assertEquals($uniqueActions, $result['actions']);
        $this->assertCount(2, $result['actions']); // REVIEW and REPORT (no duplicates)
    }

    // Helper methods
    private function mockGetCustomerRiskProfile($transaction, $riskProfile): void
    {
        $this->service = Mockery::mock(TransactionMonitoringService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
            
        $this->service->shouldReceive('getCustomerRiskProfile')
            ->with($transaction)
            ->andReturn($riskProfile);
    }

    private function mockGetApplicableRules($transaction, $riskProfile, $rules): void
    {
        $this->service->shouldReceive('getApplicableRules')
            ->with($transaction, $riskProfile)
            ->andReturn($rules);
    }

    private function mockEvaluateRule($rule, $transaction, $riskProfile, $result): void
    {
        $this->service->shouldReceive('evaluateRule')
            ->with($rule, $transaction, $riskProfile)
            ->andReturn($result);
            
        if ($result) {
            $this->service->shouldReceive('createAlert')
                ->with($rule, $transaction)
                ->andReturn(['rule_id' => $rule->id, 'rule_name' => $rule->name]);
                
            $this->service->shouldReceive('processActions')->once();
            $this->service->shouldReceive('updateBehavioralRisk')->once();
        }
    }

    private function createMockRule($id, $name, $actions): TransactionMonitoringRule
    {
        $rule = Mockery::mock(TransactionMonitoringRule::class);
        $rule->id = $id;
        $rule->name = $name;
        $rule->shouldReceive('getActions')->andReturn($actions);
        $rule->shouldReceive('recordTrigger')->once();
        
        return $rule;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}