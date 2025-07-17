<?php

namespace Tests\Unit\Domain\User\ValueObjects;

use App\Domain\User\ValueObjects\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function test_can_create_valid_email()
    {
        $email = new Email('user@example.com');
        
        $this->assertEquals('user@example.com', $email->getValue());
    }

    public function test_normalizes_email_to_lowercase()
    {
        $email = new Email('USER@EXAMPLE.COM');
        
        $this->assertEquals('user@example.com', $email->getValue());
    }

    public function test_trims_whitespace()
    {
        $email = new Email('  user@example.com  ');
        
        $this->assertEquals('user@example.com', $email->getValue());
    }

    public function test_cannot_create_empty_email()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El email no puede estar vacío');
        
        new Email('');
    }

    public function test_cannot_create_invalid_email_format()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El formato del email no es válido');
        
        new Email('invalid-email');
    }

    public function test_cannot_create_email_without_at_symbol()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El formato del email no es válido');
        
        new Email('userexample.com');
    }

    public function test_cannot_create_email_without_domain()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El formato del email no es válido');
        
        new Email('user@');
    }

    public function test_cannot_create_email_too_long()
    {
        $longEmail = str_repeat('a', 250) . '@example.com';
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El email no puede tener más de 255 caracteres');
        
        new Email($longEmail);
    }

    public function test_can_get_domain()
    {
        $email = new Email('user@example.com');
        
        $this->assertEquals('example.com', $email->getDomain());
    }

    public function test_can_get_local_part()
    {
        $email = new Email('user@example.com');
        
        $this->assertEquals('user', $email->getLocalPart());
    }

    public function test_can_compare_emails()
    {
        $email1 = new Email('user@example.com');
        $email2 = new Email('USER@EXAMPLE.COM');
        $email3 = new Email('other@example.com');
        
        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    public function test_can_convert_to_string()
    {
        $email = new Email('user@example.com');
        
        $this->assertEquals('user@example.com', (string) $email);
    }

    public function test_handles_complex_valid_emails()
    {
        $validEmails = [
            'test.email@example.com',
            'user+tag@example.co.uk',
            'user123@sub.example.org',
            'a@b.co'
        ];

        foreach ($validEmails as $emailString) {
            $email = new Email($emailString);
            $this->assertEquals(strtolower($emailString), $email->getValue());
        }
    }
}