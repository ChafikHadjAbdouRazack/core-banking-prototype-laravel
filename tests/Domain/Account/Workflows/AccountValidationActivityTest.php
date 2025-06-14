<?php

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\Workflows\AccountValidationActivity;
use App\Models\Account;
use App\Models\User;

it('class exists', function () {
    expect(class_exists(AccountValidationActivity::class))->toBeTrue();
});

it('extends Activity base class', function () {
    $reflection = new ReflectionClass(AccountValidationActivity::class);
    expect($reflection->getParentClass()->getName())->toBe('Workflow\Activity');
});

it('has execute method', function () {
    expect(method_exists(AccountValidationActivity::class, 'execute'))->toBeTrue();
});

it('execute method has correct signature', function () {
    $reflection = new ReflectionClass(AccountValidationActivity::class);
    $method = $reflection->getMethod('execute');
    
    expect($method->getNumberOfParameters())->toBe(3);
    
    $parameters = $method->getParameters();
    expect($parameters[0]->getName())->toBe('uuid');
    expect($parameters[1]->getName())->toBe('validationChecks');
    expect($parameters[2]->getName())->toBe('validatedBy');
});

it('execute method returns array', function () {
    $reflection = new ReflectionClass(AccountValidationActivity::class);
    $method = $reflection->getMethod('execute');
    
    expect($method->getReturnType()->getName())->toBe('array');
});

it('has proper type hints for parameters', function () {
    $reflection = new ReflectionClass(AccountValidationActivity::class);
    $method = $reflection->getMethod('execute');
    $parameters = $method->getParameters();
    
    expect($parameters[0]->getType()->getName())->toBe('App\Domain\Account\DataObjects\AccountUuid');
    expect($parameters[1]->getType()->getName())->toBe('array');
    expect($parameters[2]->getType()->getName())->toBe('string');
    expect($parameters[2]->allowsNull())->toBeTrue();
});

it('has validation check methods', function () {
    $methods = [
        'performValidationCheck',
        'validateKycDocuments',
        'validateAddress',
        'validateIdentity',
        'performComplianceScreening',
        'logValidation'
    ];
    
    foreach ($methods as $method) {
        expect(method_exists(AccountValidationActivity::class, $method))->toBeTrue();
    }
});

it('validation methods have proper visibility', function () {
    $reflection = new ReflectionClass(AccountValidationActivity::class);
    
    $privateMethodNames = [
        'performValidationCheck',
        'validateKycDocuments', 
        'validateAddress',
        'validateIdentity',
        'performComplianceScreening',
        'logValidation'
    ];
    
    foreach ($privateMethodNames as $methodName) {
        $method = $reflection->getMethod($methodName);
        expect($method->isPrivate())->toBeTrue();
    }
});

it('validation methods return arrays', function () {
    $reflection = new ReflectionClass(AccountValidationActivity::class);
    
    $methods = [
        'performValidationCheck',
        'validateKycDocuments',
        'validateAddress', 
        'validateIdentity',
        'performComplianceScreening'
    ];
    
    foreach ($methods as $methodName) {
        $method = $reflection->getMethod($methodName);
        expect($method->getReturnType()->getName())->toBe('array');
    }
});

// Note: Workflow Activity classes cannot be instantiated directly in tests
// as they require workflow context. Tests focus on class structure validation.