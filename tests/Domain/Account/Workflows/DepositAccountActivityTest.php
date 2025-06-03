<?php

use App\Domain\Account\Workflows\DepositAccountActivity;

it('class exists', function () {
    expect(class_exists(DepositAccountActivity::class))->toBeTrue();
});

it('extends Activity base class', function () {
    $reflection = new ReflectionClass(DepositAccountActivity::class);
    expect($reflection->getParentClass()->getName())->toBe('Workflow\Activity');
});

it('has execute method', function () {
    expect(method_exists(DepositAccountActivity::class, 'execute'))->toBeTrue();
});

it('execute method has correct signature', function () {
    $reflection = new ReflectionClass(DepositAccountActivity::class);
    $method = $reflection->getMethod('execute');
    
    expect($method->getNumberOfParameters())->toBe(3);
    
    $parameters = $method->getParameters();
    expect($parameters[0]->getName())->toBe('uuid');
    expect($parameters[1]->getName())->toBe('money');
    expect($parameters[2]->getName())->toBe('transaction');
});

it('execute method returns boolean', function () {
    $reflection = new ReflectionClass(DepositAccountActivity::class);
    $method = $reflection->getMethod('execute');
    
    expect($method->getReturnType()->getName())->toBe('bool');
});

it('has proper type hints for parameters', function () {
    $reflection = new ReflectionClass(DepositAccountActivity::class);
    $method = $reflection->getMethod('execute');
    $parameters = $method->getParameters();
    
    expect($parameters[0]->getType()->getName())->toBe('App\Domain\Account\DataObjects\AccountUuid');
    expect($parameters[1]->getType()->getName())->toBe('App\Domain\Account\DataObjects\Money');
    expect($parameters[2]->getType()->getName())->toBe('App\Domain\Account\Aggregates\TransactionAggregate');
});