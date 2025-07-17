<?php

namespace Tests\Unit\Domain\Transaction\ValueObjects;

use App\Domain\Transaction\ValueObjects\Amount;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AmountTest extends TestCase
{
    public function test_can_create_valid_amount()
    {
        $amount = new Amount(100.50);
        
        $this->assertEquals(100.50, $amount->getValue());
    }

    public function test_cannot_create_zero_amount()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El monto debe ser mayor a cero');
        
        new Amount(0.00);
    }

    public function test_cannot_create_negative_amount()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El monto debe ser mayor a cero');
        
        new Amount(-10.00);
    }

    public function test_cannot_create_amount_below_minimum()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El monto mínimo de transferencia es $0.01');
        
        new Amount(0.005);
    }

    public function test_can_check_if_exceeds_daily_limit()
    {
        $validAmount = new Amount(4999.99);
        $exceedingAmount = new Amount(5000.01);
        
        $this->assertFalse($validAmount->exceedsDailyLimit());
        $this->assertTrue($exceedingAmount->exceedsDailyLimit());
    }

    public function test_can_check_if_exceeds_custom_limit()
    {
        $amount = new Amount(150.00);
        
        $this->assertFalse($amount->exceedsLimit(200.00));
        $this->assertTrue($amount->exceedsLimit(100.00));
    }

    public function test_can_check_if_valid_for_transfer()
    {
        $validAmount = new Amount(100.00);
        $tooLargeAmount = new Amount(6000.00);
        
        $this->assertTrue($validAmount->isValidForTransfer());
        $this->assertFalse($tooLargeAmount->isValidForTransfer());
    }

    public function test_can_convert_to_string()
    {
        $amount = new Amount(1234.56);
        
        $this->assertEquals('1,234.56', $amount->toString());
        $this->assertEquals('1,234.56', (string) $amount);
    }

    public function test_can_convert_to_cents()
    {
        $amount = new Amount(12.34);
        
        $this->assertEquals(1234, $amount->toCents());
    }

    public function test_can_create_from_cents()
    {
        $amount = Amount::fromCents(1234);
        
        $this->assertEquals(12.34, $amount->getValue());
    }

    public function test_can_compare_amounts()
    {
        $amount1 = new Amount(100.00);
        $amount2 = new Amount(100.00);
        $amount3 = new Amount(200.00);
        
        $this->assertTrue($amount1->equals($amount2));
        $this->assertFalse($amount1->equals($amount3));
    }

    public function test_equals_handles_float_precision()
    {
        $amount1 = new Amount(0.1 + 0.2);
        $amount2 = new Amount(0.3);
        
        $this->assertTrue($amount1->equals($amount2));
    }
}