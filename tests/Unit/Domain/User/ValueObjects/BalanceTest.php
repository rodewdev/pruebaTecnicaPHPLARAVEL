<?php

namespace Tests\Unit\Domain\User\ValueObjects;

use App\Domain\User\ValueObjects\Balance;
use App\Domain\Transaction\ValueObjects\Amount;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BalanceTest extends TestCase
{
    public function test_can_create_valid_balance()
    {
        $balance = new Balance(100.50);
        
        $this->assertEquals(100.50, $balance->getValue());
    }

    public function test_cannot_create_negative_balance()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El saldo no puede ser negativo');
        
        new Balance(-10.00);
    }

    public function test_can_afford_transfer_when_sufficient_balance()
    {
        $balance = new Balance(100.00);
        $amount = new Amount(50.00);
        
        $this->assertTrue($balance->canAfford($amount));
    }

    public function test_cannot_afford_transfer_when_insufficient_balance()
    {
        $balance = new Balance(30.00);
        $amount = new Amount(50.00);
        
        $this->assertFalse($balance->canAfford($amount));
    }

    public function test_can_subtract_amount()
    {
        $balance = new Balance(100.00);
        $amount = new Amount(30.00);
        
        $newBalance = $balance->subtract($amount);
        
        $this->assertEquals(70.00, $newBalance->getValue());
    }

    public function test_cannot_subtract_amount_resulting_in_negative_balance()
    {
        $balance = new Balance(20.00);
        $amount = new Amount(30.00);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El saldo resultante no puede ser negativo');
        
        $balance->subtract($amount);
    }

    public function test_can_add_amount()
    {
        $balance = new Balance(50.00);
        $amount = new Amount(25.00);
        
        $newBalance = $balance->add($amount);
        
        $this->assertEquals(75.00, $newBalance->getValue());
    }

    public function test_can_check_if_balance_is_zero()
    {
        $zeroBalance = new Balance(0.00);
        $nonZeroBalance = new Balance(10.00);
        
        $this->assertTrue($zeroBalance->isZero());
        $this->assertFalse($nonZeroBalance->isZero());
    }

    public function test_can_convert_to_string()
    {
        $balance = new Balance(1234.56);
        
        $this->assertEquals('1,234.56', $balance->toString());
    }

    public function test_can_compare_balances()
    {
        $balance1 = new Balance(100.00);
        $balance2 = new Balance(100.00);
        $balance3 = new Balance(200.00);
        
        $this->assertTrue($balance1->equals($balance2));
        $this->assertFalse($balance1->equals($balance3));
    }
}