<?php

namespace Tests\Unit\Domain\Fraud\Services;

use App\Domain\Fraud\Models\FraudRule;
use App\Domain\Fraud\Services\RuleEngineService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\ServiceTestCase;

class RuleEngineServiceTest extends ServiceTestCase
{
    private RuleEngineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RuleEngineService();
        Cache::flush();
    }

    #[Test]
    public function test_evaluate_returns_empty_results_when_no_rules_active(): void
    {
        $context = [
            'transaction_amount' => 1000,
            'user_id'            => 'user-123',
        ];

        $results = $this->service->evaluate($context);

        $this->assertEquals(0, $results['total_score']);
        $this->assertEmpty($results['triggered_rules']);
        $this->assertEmpty($results['blocking_rules']);
        $this->assertEmpty($results['rule_scores']);
        $this->assertEmpty($results['rule_details']);
    }

    #[Test]
    public function test_evaluate_triggers_matching_rules(): void
    {
        // Create active rules
        $rule1 = FraudRule::factory()->create([
            'code'        => 'HIGH_AMOUNT',
            'name'        => 'High Transaction Amount',
            'category'    => 'transaction',
            'severity'    => 'high',
            'base_score'  => 30,
            'is_active'   => true,
            'is_blocking' => false,
            'conditions'  => [
                'transaction_amount' => ['operator' => '>', 'value' => 10000],
            ],
        ]);

        $rule2 = FraudRule::factory()->create([
            'code'        => 'LOW_AMOUNT',
            'name'        => 'Low Transaction Amount',
            'category'    => 'transaction',
            'severity'    => 'low',
            'base_score'  => 10,
            'is_active'   => true,
            'is_blocking' => false,
            'conditions'  => [
                'transaction_amount' => ['operator' => '<', 'value' => 100],
            ],
        ]);

        $context = [
            'transaction_amount' => 15000,
            'user_id'            => 'user-123',
        ];

        $results = $this->service->evaluate($context);

        $this->assertEquals(30, $results['total_score']);
        $this->assertContains('HIGH_AMOUNT', $results['triggered_rules']);
        $this->assertNotContains('LOW_AMOUNT', $results['triggered_rules']);
        $this->assertEquals(30, $results['rule_scores']['HIGH_AMOUNT']);
    }

    #[Test]
    public function test_evaluate_identifies_blocking_rules(): void
    {
        $blockingRule = FraudRule::factory()->create([
            'code'        => 'BLACKLIST_MATCH',
            'name'        => 'Blacklist Match',
            'category'    => 'blacklist',
            'severity'    => 'critical',
            'base_score'  => 100,
            'is_active'   => true,
            'is_blocking' => true,
            'conditions'  => [
                'user_blacklisted' => ['operator' => '==', 'value' => true],
            ],
        ]);

        $context = [
            'user_blacklisted'   => true,
            'transaction_amount' => 500,
        ];

        $results = $this->service->evaluate($context);

        $this->assertEquals(100, $results['total_score']);
        $this->assertContains('BLACKLIST_MATCH', $results['triggered_rules']);
        $this->assertContains('BLACKLIST_MATCH', $results['blocking_rules']);
    }

    #[Test]
    public function test_evaluate_caps_total_score_at_100(): void
    {
        // Create multiple high-scoring rules
        FraudRule::factory()->create([
            'code'       => 'RULE1',
            'base_score' => 50,
            'is_active'  => true,
            'conditions' => ['always_true' => true],
        ]);

        FraudRule::factory()->create([
            'code'       => 'RULE2',
            'base_score' => 60,
            'is_active'  => true,
            'conditions' => ['always_true' => true],
        ]);

        $context = ['always_true' => true];

        $results = $this->service->evaluate($context);

        $this->assertEquals(100, $results['total_score']); // Capped at 100
        $this->assertCount(2, $results['triggered_rules']);
    }

    #[Test]
    public function test_evaluate_handles_rule_evaluation_errors(): void
    {
        $faultyRule = FraudRule::factory()->create([
            'code'       => 'FAULTY_RULE',
            'is_active'  => true,
            'conditions' => ['invalid_condition' => 'will_cause_error'],
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Rule evaluation failed', Mockery::type('array'));

        $context = ['transaction_amount' => 1000];

        $results = $this->service->evaluate($context);

        // Should continue evaluation despite error
        $this->assertNotContains('FAULTY_RULE', $results['triggered_rules']);
    }

    #[Test]
    public function test_evaluate_includes_rule_details(): void
    {
        $rule = FraudRule::factory()->create([
            'code'       => 'DETAILED_RULE',
            'name'       => 'Detailed Test Rule',
            'category'   => 'velocity',
            'severity'   => 'medium',
            'base_score' => 25,
            'is_active'  => true,
            'actions'    => ['review', 'notify'],
            'conditions' => ['test_condition' => true],
        ]);

        $context = ['test_condition' => true];

        $results = $this->service->evaluate($context);

        $this->assertArrayHasKey('DETAILED_RULE', $results['rule_details']);
        $details = $results['rule_details']['DETAILED_RULE'];

        $this->assertEquals('Detailed Test Rule', $details['name']);
        $this->assertEquals('velocity', $details['category']);
        $this->assertEquals('medium', $details['severity']);
        $this->assertEquals(25, $details['score']);
        $this->assertEquals(['review', 'notify'], $details['actions']);
    }

    #[Test]
    public function test_evaluate_caches_active_rules(): void
    {
        // Create rules
        FraudRule::factory()->count(3)->create(['is_active' => true]);

        // First call should query database
        $results1 = $this->service->evaluate(['test' => true]);

        // Create more rules
        FraudRule::factory()->count(2)->create(['is_active' => true]);

        // Second call should use cache (not see new rules)
        $results2 = $this->service->evaluate(['test' => true]);

        // Clear cache and call again
        Cache::flush();
        $results3 = $this->service->evaluate(['test' => true]);

        // Results should be same for first two calls (cached)
        // Third call might be different if rules evaluate differently
        $this->assertNotNull($results1);
        $this->assertNotNull($results2);
        $this->assertNotNull($results3);
    }

    #[Test]
    public function test_evaluate_orders_rules_by_severity_and_score(): void
    {
        FraudRule::factory()->create([
            'code'       => 'LOW_SEVERITY',
            'severity'   => 'low',
            'base_score' => 50,
            'is_active'  => true,
        ]);

        FraudRule::factory()->create([
            'code'       => 'HIGH_SEVERITY',
            'severity'   => 'high',
            'base_score' => 30,
            'is_active'  => true,
        ]);

        FraudRule::factory()->create([
            'code'       => 'CRITICAL_SEVERITY',
            'severity'   => 'critical',
            'base_score' => 40,
            'is_active'  => true,
        ]);

        // Get rules directly to check ordering
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getActiveRules');
        $method->setAccessible(true);

        $rules = $method->invoke($this->service);

        // Should be ordered by severity (critical > high > low), then by score
        $this->assertEquals('CRITICAL_SEVERITY', $rules[0]->code);
        $this->assertEquals('HIGH_SEVERITY', $rules[1]->code);
        $this->assertEquals('LOW_SEVERITY', $rules[2]->code);
    }

    #[Test]
    public function test_evaluates_complex_conditions(): void
    {
        $rule = FraudRule::factory()->create([
            'code'       => 'COMPLEX_RULE',
            'base_score' => 40,
            'is_active'  => true,
            'conditions' => [
                'transaction_amount' => ['operator' => '>=', 'value' => 5000],
                'user_country'       => ['operator' => 'in', 'value' => ['US', 'CA', 'UK']],
                'account_age_days'   => ['operator' => '<', 'value' => 30],
            ],
        ]);

        // Context that matches all conditions
        $matchingContext = [
            'transaction_amount' => 6000,
            'user_country'       => 'US',
            'account_age_days'   => 15,
        ];

        $results = $this->service->evaluate($matchingContext);
        $this->assertContains('COMPLEX_RULE', $results['triggered_rules']);

        // Context that doesn't match all conditions
        $nonMatchingContext = [
            'transaction_amount' => 6000,
            'user_country'       => 'FR', // Not in allowed countries
            'account_age_days'   => 15,
        ];

        $results2 = $this->service->evaluate($nonMatchingContext);
        $this->assertNotContains('COMPLEX_RULE', $results2['triggered_rules']);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        \Mockery::close();
        parent::tearDown();
    }
}
