<?php

namespace App\Domain\Transaction\ValueObjects;

use InvalidArgumentException;

class Amount
{
    private float $value;
    private const DAILY_LIMIT = 5000.00;
    private const MIN_AMOUNT = 0.01;

    public function __construct(float $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('El monto debe ser mayor a cero');
        }
        
        if ($value < self::MIN_AMOUNT) {
            throw new InvalidArgumentException('El monto mínimo de transferencia es $' . self::MIN_AMOUNT);
        }
        
        $this->value = $value;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function exceedsLimit(float $limit = self::DAILY_LIMIT): bool
    {
        return $this->value > $limit;
    }

    public function exceedsDailyLimit(): bool
    {
        return $this->exceedsLimit(self::DAILY_LIMIT);
    }

    public function isValidForTransfer(): bool
    {
        return $this->value >= self::MIN_AMOUNT && !$this->exceedsDailyLimit();
    }

    public function toString(): string
    {
        return number_format($this->value, 2);
    }

    public function toCents(): int
    {
        return (int) round($this->value * 100);
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents / 100);
    }

    public function equals(Amount $other): bool
    {
        return abs($this->value - $other->value) < 0.001;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}