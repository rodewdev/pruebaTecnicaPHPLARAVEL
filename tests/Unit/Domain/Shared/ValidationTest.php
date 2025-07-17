<?php

namespace Tests\Unit\Domain\Shared;

use App\Domain\Transaction\ValueObjects\Amount;
use App\Domain\User\ValueObjects\Balance;
use App\Domain\User\ValueObjects\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    public function test_daily_limit_validation()
    {
        $validAmount = new Amount(4999.99);
        $this->assertFalse($validAmount->exceedsDailyLimit());
        
        $invalidAmount = new Amount(5000.01);
        $this->assertTrue($invalidAmount->exceedsDailyLimit());
    }

    public function test_balance_operations()
    {
        $balance = new Balance(1000.00);
        $transferAmount = new Amount(300.00);
        
        $this->assertTrue($balance->canAfford($transferAmount));
        
        $newBalance = $balance->subtract($transferAmount);
        $this->assertEquals(700.00, $newBalance->getValue());
    }

    public function test_email_validation()
    {
        $validEmail = new Email('test@example.com');
        $this->assertEquals('test@example.com', $validEmail->getValue());
        
        $this->expectException(InvalidArgumentException::class);
        new Email('invalid-email');
    }

    public function test_amount_minimum_validation()
    {
        $validAmount = new Amount(0.01);
        $this->assertEquals(0.01, $validAmount->getValue());
        
        $this->expectException(InvalidArgumentException::class);
        new Amount(0.005);
    }

    public function test_negative_balance_prevention()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El saldo no puede ser negativo');
        
        new Balance(-100.00);
    }

    public function test_zero_amount_prevention()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El monto debe ser mayor a cero');
        
        new Amount(0.00);
    }

    public function test_business_rules_integration()
    {
        $userBalance = new Balance(1000.00);
        $transferAmount = new Amount(500.00);
        
        $this->assertTrue($userBalance->canAfford($transferAmount));
        $this->assertFalse($transferAmount->exceedsDailyLimit());
        $this->assertTrue($transferAmount->isValidForTransfer());
        
        $newBalance = $userBalance->subtract($transferAmount);
        $this->assertEquals(500.00, $newBalance->getValue());
    }

    public function test_email_normalization()
    {
        $email1 = new Email('TEST@EXAMPLE.COM');
        $email2 = new Email('test@example.com');
        
        $this->assertEquals('test@example.com', $email1->getValue());
        $this->assertTrue($email1->equals($email2));
    }

    public function test_amount_precision()
    {
        $amount1 = new Amount(100.50);
        $amount2 = new Amount(100.50);
        
        $this->assertTrue($amount1->equals($amount2));
        $this->assertEquals('100.50', $amount1->toString());
    }
}