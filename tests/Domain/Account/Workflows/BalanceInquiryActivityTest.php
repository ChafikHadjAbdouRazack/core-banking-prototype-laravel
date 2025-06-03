<?php

use App\Domain\Account\Workflows\BalanceInquiryActivity;

it('class exists', function () {
    expect(class_exists(BalanceInquiryActivity::class))->toBeTrue();
});

it('extends Activity base class', function () {
    $reflection = new ReflectionClass(BalanceInquiryActivity::class);
    expect($reflection->getParentClass()->getName())->toBe('Workflow\Activity');
});

it('has execute method', function () {
    expect(method_exists(BalanceInquiryActivity::class, 'execute'))->toBeTrue();
});

it('has logInquiry method', function () {
    $reflection = new ReflectionClass(BalanceInquiryActivity::class);
    $method = $reflection->getMethod('logInquiry');
    
    expect($method->isPrivate())->toBeTrue();
});

it('execute method has correct signature', function () {
    $reflection = new ReflectionClass(BalanceInquiryActivity::class);
    $method = $reflection->getMethod('execute');
    
    expect($method->getNumberOfParameters())->toBe(3);
    
    $parameters = $method->getParameters();
    expect($parameters[0]->getName())->toBe('uuid');
    expect($parameters[1]->getName())->toBe('requestedBy');
    expect($parameters[2]->getName())->toBe('transaction');
});