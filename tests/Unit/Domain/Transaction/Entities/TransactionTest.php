<?php

namespace Tests\Unit\Domain\Transaction\Entities;

use App\Domain\Transaction\Entities\Transaction;
use App\Domain\Transaction\ValueObjects\Amount;
use App\Domain\Transaction\ValueObjects\TransactionType;
use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    private function createValidTransaction(): Transaction
    {
        return new Transaction(
            1, // sender_id
            2, // receiver_id
            new Amount(100.00),
            TransactionType::transfer(),
            'TXN-123456'
        );
    }

    public function test_can_create_valid_transaction()
    {
        $transaction = $this->createValidTransaction();
        
        $this->assertEquals(1, $transaction->getSenderId());
        $this->assertEquals(2, $transaction->getReceiverId());
        $this->assertEquals(100.00, $transaction->getAmount()->getValue());
        $this->assertTrue($transaction->getType()->isTransfer());
        $this->assertEquals('TXN-123456', $transaction->getReference());
        $this->assertEquals(Transaction::STATUS_PENDING, $transaction->getStatus());
        $this->assertInstanceOf(Carbon::class, $transaction->getCreatedAt());
        $this->assertInstanceOf(Carbon::class, $transaction->getUpdatedAt());
    }

    public function test_cannot_create_transaction_with_same_sender_and_receiver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El emisor y receptor no pueden ser el mismo usuario');
        
        new Transaction(
            1, // sender_id
            1, // receiver_id 
            new Amount(100.00),
            TransactionType::transfer(),
            'TXN-123456'
        );
    }

    public function test_cannot_create_transaction_with_invalid_user_ids()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Los IDs de emisor y receptor deben ser válidos');
        
        new Transaction(
            0, // inválido sender_id
            2,
            new Amount(100.00),
            TransactionType::transfer(),
            'TXN-123456'
        );
    }

    public function test_cannot_create_transaction_with_empty_reference()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La referencia no puede estar vacía');
        
        new Transaction(
            1,
            2,
            new Amount(100.00),
            TransactionType::transfer(),
            ''
        );
    }

    public function test_cannot_create_transaction_with_long_reference()
    {
        $longReference = str_repeat('a', 256);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La referencia no puede tener más de 255 caracteres');
        
        new Transaction(
            1,
            2,
            new Amount(100.00),
            TransactionType::transfer(),
            $longReference
        );
    }

    public function test_cannot_create_transaction_with_invalid_status()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Estado inválido. Estados válidos: pending, completed, failed');
        
        new Transaction(
            1,
            2,
            new Amount(100.00),
            TransactionType::transfer(),
            'TXN-123456',
            null,
            null,
            'invalid_status'
        );
    }

    public function test_can_mark_as_completed()
    {
        $transaction = $this->createValidTransaction();
        
        $this->assertTrue($transaction->isPending());
        $this->assertFalse($transaction->isCompleted());
        
        $transaction->markAsCompleted();
        
        $this->assertFalse($transaction->isPending());
        $this->assertTrue($transaction->isCompleted());
        $this->assertEquals(Transaction::STATUS_COMPLETED, $transaction->getStatus());
    }

    public function test_can_mark_as_failed()
    {
        $transaction = $this->createValidTransaction();
        
        $this->assertTrue($transaction->isPending());
        $this->assertFalse($transaction->isFailed());
        
        $transaction->markAsFailed();
        
        $this->assertFalse($transaction->isPending());
        $this->assertTrue($transaction->isFailed());
        $this->assertEquals(Transaction::STATUS_FAILED, $transaction->getStatus());
    }

    public function test_can_update_description()
    {
        $transaction = $this->createValidTransaction();
        
        $transaction->updateDescription('Payment for services');
        
        $this->assertEquals('Payment for services', $transaction->getDescription());
    }

    public function test_can_add_metadata()
    {
        $transaction = $this->createValidTransaction();
        
        $transaction->addMetadata(['key1' => 'value1']);
        $transaction->addMetadata(['key2' => 'value2']);
        
        $metadata = $transaction->getMetadata();
        $this->assertEquals('value1', $metadata['key1']);
        $this->assertEquals('value2', $metadata['key2']);
    }

    public function test_can_check_if_can_be_processed()
    {
        $validTransaction = $this->createValidTransaction();
        $completedTransaction = $this->createValidTransaction();
        $completedTransaction->markAsCompleted();
        
        $this->assertTrue($validTransaction->canBeProcessed());
        $this->assertFalse($completedTransaction->canBeProcessed());
    }

    public function test_can_generate_reference()
    {
        $reference = Transaction::generateReference();
        
        $this->assertIsString($reference);
        $this->assertStringStartsWith('TXN-', $reference);
        $this->assertGreaterThan(10, strlen($reference));
    }

    public function test_can_convert_to_array()
    {
        $transaction = $this->createValidTransaction();
        $array = $transaction->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(1, $array['sender_id']);
        $this->assertEquals(2, $array['receiver_id']);
        $this->assertEquals(100.00, $array['amount']);
        $this->assertEquals('transfer', $array['type']);
        $this->assertEquals('pending', $array['status']);
        $this->assertEquals('TXN-123456', $array['reference']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    public function test_can_create_with_optional_parameters()
    {
        $transaction = new Transaction(
            1,
            2,
            new Amount(100.00),
            TransactionType::transfer(),
            'TXN-123456',
            'Test description',
            ['key' => 'value'],
            Transaction::STATUS_COMPLETED
        );
        
        $this->assertEquals('Test description', $transaction->getDescription());
        $this->assertEquals(['key' => 'value'], $transaction->getMetadata());
        $this->assertTrue($transaction->isCompleted());
    }
}