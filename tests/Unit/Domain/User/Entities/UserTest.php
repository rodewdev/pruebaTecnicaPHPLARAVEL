<?php

namespace Tests\Unit\Domain\User\Entities;

use App\Domain\User\Entities\User;
use App\Domain\User\ValueObjects\Balance;
use App\Domain\User\ValueObjects\Email;
use App\Domain\Transaction\ValueObjects\Amount;
use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private function createValidUser(): User
    {
        return new User(
            'John Doe',
            new Email('john@example.com'),
            new Balance(100.00)
        );
    }

    public function test_can_create_valid_user()
    {
        $user = $this->createValidUser();
        
        $this->assertEquals('John Doe', $user->getName());
        $this->assertEquals('john@example.com', $user->getEmail()->getValue());
        $this->assertEquals(100.00, $user->getBalance()->getValue());
        $this->assertNull($user->getId());
        $this->assertInstanceOf(Carbon::class, $user->getCreatedAt());
        $this->assertInstanceOf(Carbon::class, $user->getUpdatedAt());
    }

    public function test_cannot_create_user_with_empty_name()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre no puede estar vacío');
        
        new User(
            '',
            new Email('john@example.com'),
            new Balance(100.00)
        );
    }

    public function test_cannot_create_user_with_long_name()
    {
        $longName = str_repeat('a', 256);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre no puede tener más de 255 caracteres');
        
        new User(
            $longName,
            new Email('john@example.com'),
            new Balance(100.00)
        );
    }

    public function test_can_check_if_can_afford_transfer()
    {
        $user = $this->createValidUser();
        $smallAmount = new Amount(50.00);
        $largeAmount = new Amount(150.00);
        
        $this->assertTrue($user->canAffordTransfer($smallAmount));
        $this->assertFalse($user->canAffordTransfer($largeAmount));
    }

    public function test_can_debit_amount()
    {
        $user = $this->createValidUser();
        $amount = new Amount(30.00);
        
        $user->debit($amount);
        
        $this->assertEquals(70.00, $user->getBalance()->getValue());
    }

    public function test_cannot_debit_insufficient_amount()
    {
        $user = $this->createValidUser();
        $amount = new Amount(150.00);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fondos insuficientes para realizar la transferencia');
        
        $user->debit($amount);
    }

    public function test_can_credit_amount()
    {
        $user = $this->createValidUser();
        $amount = new Amount(50.00);
        
        $user->credit($amount);
        
        $this->assertEquals(150.00, $user->getBalance()->getValue());
    }

    public function test_can_update_name()
    {
        $user = $this->createValidUser();
        
        $user->updateName('Jane Doe');
        
        $this->assertEquals('Jane Doe', $user->getName());
    }

    public function test_can_update_email()
    {
        $user = $this->createValidUser();
        $newEmail = new Email('jane@example.com');
        
        $user->updateEmail($newEmail);
        
        $this->assertEquals('jane@example.com', $user->getEmail()->getValue());
        $this->assertNull($user->getEmailVerifiedAt());
    }

    public function test_can_verify_email()
    {
        $user = $this->createValidUser();
        
        $this->assertFalse($user->isEmailVerified());
        
        $user->verifyEmail();
        
        $this->assertTrue($user->isEmailVerified());
        $this->assertInstanceOf(Carbon::class, $user->getEmailVerifiedAt());
    }

    public function test_can_soft_delete_user()
    {
        $user = $this->createValidUser();
        
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isDeleted());
        
        $user->softDelete();
        
        $this->assertFalse($user->isActive());
        $this->assertTrue($user->isDeleted());
        $this->assertInstanceOf(Carbon::class, $user->getDeletedAt());
    }

    public function test_can_restore_user()
    {
        $user = $this->createValidUser();
        $user->softDelete();
        
        $user->restore();
        
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isDeleted());
        $this->assertNull($user->getDeletedAt());
    }

    public function test_can_convert_to_array()
    {
        $user = $this->createValidUser();
        $array = $user->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('John Doe', $array['name']);
        $this->assertEquals('john@example.com', $array['email']);
        $this->assertEquals(100.00, $array['balance']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }
}