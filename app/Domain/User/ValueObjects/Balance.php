<?php

namespace App\Domain\User\ValueObjects;

use App\Domain\Transaction\ValueObjects\Amount;
use InvalidArgumentException;

class Balance
{
    private float $amount;

    public function __construct(float $amount)
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('El saldo no puede ser negativo');
        }
        $this->amount = $amount;
    }

    public function getValue(): float
    {
        return $this->amount;
    }

    public function canAfford(Amount $transferAmount): bool
    {
        return $this->amount >= $transferAmount->getValue();
    }

    public function subtract(Amount $amount): self
    {
        $newAmount = $this->amount - $amount->getValue();
        if ($newAmount < 0) {
            throw new InvalidArgumentException('El saldo resultante no puede ser negativo');
        }
        return new self($newAmount);
    }

    public function add(Amount $amount): self
    {
        return new self($this->amount + $amount->getValue());
    }

    public function isZero(): bool
    {
        return $this->amount === 0.0;
    }

    public function toString(): string
    {
        return number_format($this->amount, 2);
    }

    public function equals(Balance $other): bool
    {
        return abs($this->amount - $other->amount) < 0.01;
    }
}
