<?php

namespace App\Domain\User\ValueObjects;

use InvalidArgumentException;

class Email
{
    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = strtolower(trim($value));
    }

    private function validate(string $email): void
    {
        $trimmedEmail = trim($email);
        
        if (empty($trimmedEmail)) {
            throw new InvalidArgumentException('El email no puede estar vacío');
        }

        if (strlen($trimmedEmail) > 255) {
            throw new InvalidArgumentException('El email no puede tener más de 255 caracteres');
        }

        if (!filter_var($trimmedEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El formato del email no es válido');
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function getLocalPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}