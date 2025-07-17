<?php

namespace Tests\Unit\Domain\Transaction\ValueObjects;

use App\Domain\Transaction\ValueObjects\TransactionType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TransactionTypeTest extends TestCase
{
    public function test_can_create_transfer_type()
    {
        $type = new TransactionType(TransactionType::TRANSFER);
        
        $this->assertEquals('transfer', $type->getValue());
        $this->assertTrue($type->isTransfer());
        $this->assertFalse($type->isDeposit());
        $this->assertFalse($type->isWithdrawal());
    }

    public function test_can_create_deposit_type()
    {
        $type = new TransactionType(TransactionType::DEPOSIT);
        
        $this->assertEquals('deposit', $type->getValue());
        $this->assertTrue($type->isDeposit());
        $this->assertFalse($type->isTransfer());
        $this->assertFalse($type->isWithdrawal());
    }

    public function test_can_create_withdrawal_type()
    {
        $type = new TransactionType(TransactionType::WITHDRAWAL);
        
        $this->assertEquals('withdrawal', $type->getValue());
        $this->assertTrue($type->isWithdrawal());
        $this->assertFalse($type->isTransfer());
        $this->assertFalse($type->isDeposit());
    }

    public function test_cannot_create_invalid_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipo de transacción inválido. Tipos válidos: transfer, deposit, withdrawal');
        
        new TransactionType('invalid_type');
    }

    public function test_can_create_using_static_methods()
    {
        $transfer = TransactionType::transfer();
        $deposit = TransactionType::deposit();
        $withdrawal = TransactionType::withdrawal();
        
        $this->assertTrue($transfer->isTransfer());
        $this->assertTrue($deposit->isDeposit());
        $this->assertTrue($withdrawal->isWithdrawal());
    }

    public function test_can_compare_transaction_types()
    {
        $type1 = TransactionType::transfer();
        $type2 = new TransactionType(TransactionType::TRANSFER);
        $type3 = TransactionType::deposit();
        
        $this->assertTrue($type1->equals($type2));
        $this->assertFalse($type1->equals($type3));
    }

    public function test_can_convert_to_string()
    {
        $type = TransactionType::transfer();
        
        $this->assertEquals('transfer', (string) $type);
    }

    public function test_all_constants_are_valid()
    {
        $validTypes = [
            TransactionType::TRANSFER,
            TransactionType::DEPOSIT,
            TransactionType::WITHDRAWAL
        ];

        foreach ($validTypes as $typeValue) {
            $type = new TransactionType($typeValue);
            $this->assertEquals($typeValue, $type->getValue());
        }
    }
}