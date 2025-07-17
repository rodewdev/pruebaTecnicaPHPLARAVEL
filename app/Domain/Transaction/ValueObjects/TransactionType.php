<?php

namespace App\Domain\Transaction\ValueObjects;

use InvalidArgumentException;

class TransactionType
{
    public const TRANSFER = 'transfer';
    public const DEPOSIT = 'deposit';
    public const WITHDRAWAL = 'withdrawal';

    private const VALID_TYPES = [
        self::TRANSFER,
        self::DEPOSIT,
        self::WITHDRAWAL,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (!in_array($value, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                'Tipo de transacción inválido. Tipos válidos: ' . implode(', ', self::VALID_TYPES)
            );
        }
        
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isTransfer(): bool
    {
        return $this->value === self::TRANSFER;
    }

    public function isDeposit(): bool
    {
        return $this->value === self::DEPOSIT;
    }

    public function isWithdrawal(): bool
    {
        return $this->value === self::WITHDRAWAL;
    }

    public function equals(TransactionType $other): bool
    {
        return $this->value === $other->value;
    }

    public static function transfer(): self
    {
        return new self(self::TRANSFER);
    }

    public static function deposit(): self
    {
        return new self(self::DEPOSIT);
    }

    public static function withdrawal(): self
    {
        return new self(self::WITHDRAWAL);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}