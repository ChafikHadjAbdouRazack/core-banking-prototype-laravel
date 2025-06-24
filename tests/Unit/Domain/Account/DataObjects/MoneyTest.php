<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Account\DataObjects;

use App\Domain\Account\DataObjects\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_can_create_money_object()
    {
        $money = new Money(1000);
        
        $this->assertEquals(1000, $money->amount);
    }

    public function test_can_create_money_from_string()
    {
        $money = Money::fromString('15.50');
        
        $this->assertEquals(1550, $money->amount);
    }

    public function test_can_create_money_from_cents()
    {
        $money = Money::fromCents(2500);
        
        $this->assertEquals(2500, $money->amount);
    }

    public function test_can_convert_to_string()
    {
        $money = new Money(1250);
        
        $this->assertEquals('12.50', (string) $money);
    }

    public function test_can_convert_to_dollars()
    {
        $money = new Money(3750);
        
        $this->assertEquals(37.50, $money->toDollars());
    }

    public function test_can_add_money()
    {
        $money1 = new Money(1000);
        $money2 = new Money(500);
        
        $result = $money1->add($money2);
        
        $this->assertEquals(1500, $result->amount);
    }

    public function test_can_subtract_money()
    {
        $money1 = new Money(1000);
        $money2 = new Money(300);
        
        $result = $money1->subtract($money2);
        
        $this->assertEquals(700, $result->amount);
    }

    public function test_can_multiply_money()
    {
        $money = new Money(500);
        
        $result = $money->multiply(3);
        
        $this->assertEquals(1500, $result->amount);
    }

    public function test_can_check_if_zero()
    {
        $zeroMoney = new Money(0);
        $nonZeroMoney = new Money(100);
        
        $this->assertTrue($zeroMoney->isZero());
        $this->assertFalse($nonZeroMoney->isZero());
    }

    public function test_can_check_if_positive()
    {
        $positiveMoney = new Money(100);
        $zeroMoney = new Money(0);
        $negativeMoney = new Money(-100);
        
        $this->assertTrue($positiveMoney->isPositive());
        $this->assertFalse($zeroMoney->isPositive());
        $this->assertFalse($negativeMoney->isPositive());
    }

    public function test_can_check_if_negative()
    {
        $positiveMoney = new Money(100);
        $zeroMoney = new Money(0);
        $negativeMoney = new Money(-100);
        
        $this->assertFalse($positiveMoney->isNegative());
        $this->assertFalse($zeroMoney->isNegative());
        $this->assertTrue($negativeMoney->isNegative());
    }

    public function test_can_compare_money()
    {
        $money1 = new Money(1000);
        $money2 = new Money(1000);
        $money3 = new Money(500);
        
        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
        $this->assertTrue($money1->greaterThan($money3));
        $this->assertTrue($money3->lessThan($money1));
    }

    public function test_validates_amount()
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::fromString('invalid');
    }

    public function test_handles_decimal_precision()
    {
        $money = Money::fromString('10.999');
        
        // Should round to nearest cent
        $this->assertEquals(1100, $money->amount);
    }
}