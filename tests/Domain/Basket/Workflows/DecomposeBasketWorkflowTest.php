<?php

declare(strict_types=1);

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Basket\Workflows\DecomposeBasketWorkflow;
use Workflow\WorkflowStub;

it('can create workflow stub for decompose basket', function () {
    expect(class_exists(DecomposeBasketWorkflow::class))->toBeTrue();
    
    $workflow = WorkflowStub::make(DecomposeBasketWorkflow::class);
    expect($workflow)->toBeInstanceOf(WorkflowStub::class);
});

it('has execute method with correct signature', function () {
    $reflection = new ReflectionClass(DecomposeBasketWorkflow::class);
    $method = $reflection->getMethod('execute');
    
    expect($method->isPublic())->toBeTrue();
    expect($method->getNumberOfParameters())->toBe(1);
    expect($method->getReturnType()->getName())->toBe('Generator');
});

it('extends workflow base class', function () {
    $reflection = new ReflectionClass(DecomposeBasketWorkflow::class);
    expect($reflection->getParentClass()->getName())->toBe('Workflow\Workflow');
});

it('has correct parameter types', function () {
    $reflection = new ReflectionClass(DecomposeBasketWorkflow::class);
    $method = $reflection->getMethod('execute');
    $parameters = $method->getParameters();
    
    expect($parameters[0]->getName())->toBe('input');
    
    // Check parameter type
    expect($parameters[0]->getType()->getName())->toBe('array');
});