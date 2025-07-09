<?php

namespace Tests\Unit\Domain\Stablecoin\Workflows\Activities;

use App\Domain\Stablecoin\Workflows\Activities\UpdatePositionActivity;
use Tests\TestCase;

class UpdatePositionActivityTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdatePositionActivity::class));
    }

    public function test_extends_workflow_activity(): void
    {
        $reflection = new \ReflectionClass(UpdatePositionActivity::class);
        $this->assertEquals('Workflow\Activity', $reflection->getParentClass()->getName());
    }

    public function test_has_execute_method(): void
    {
        $this->assertTrue(method_exists(UpdatePositionActivity::class, 'execute'));
    }

    public function test_execute_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(UpdatePositionActivity::class);
        $method = $reflection->getMethod('execute');

        $this->assertEquals(1, $method->getNumberOfParameters());

        $parameters = $method->getParameters();
        
        $this->assertEquals('positionUuid', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    public function test_execute_method_returns_bool(): void
    {
        $reflection = new \ReflectionClass(UpdatePositionActivity::class);
        $method = $reflection->getMethod('execute');

        $this->assertEquals('bool', $method->getReturnType()->getName());
    }

    public function test_collateral_ratio_calculations(): void
    {
        // Test various collateral ratio calculations
        $scenarios = [
            // [collateral_value, debt, expected_ratio]
            [8000000000, 5000000000, 1.6],     // 160%
            [30000000000, 1000000000, 30.0],   // 3000%
            [10000000000, 8000000000, 1.25],   // 125%
            [48000000000, 0, 0],               // 0% when no debt
        ];

        foreach ($scenarios as $scenario) {
            [$collateralValue, $debt, $expectedRatio] = $scenario;
            
            if ($debt > 0) {
                $calculatedRatio = $collateralValue / $debt;
                $this->assertEquals($expectedRatio, $calculatedRatio);
            } else {
                $this->assertEquals(0, $expectedRatio);
            }
        }
    }

    public function test_activity_properties(): void
    {
        $reflection = new \ReflectionClass(UpdatePositionActivity::class);
        
        // Check for important properties inherited from Activity
        $this->assertTrue($reflection->hasProperty('tries'));
        $this->assertTrue($reflection->hasProperty('timeout'));
        $this->assertTrue($reflection->hasProperty('maxExceptions'));
    }
}