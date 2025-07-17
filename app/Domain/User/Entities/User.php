<?php

namespace App\Domain\User\Entities;

use App\Domain\User\ValueObjects\Balance;
use App\Domain\User\ValueObjects\Email;
use App\Domain\Transaction\ValueObjects\Amount;
use Carbon\Carbon;

class User
{
    private ?int $id;
    private string $name;
    private Email $email;
    private Balance $balance;
    private ?Carbon $emailVerifiedAt;
    private ?Carbon $createdAt;
    private ?Carbon $updatedAt;
    private ?Carbon $deletedAt;

    public function __construct(
        string $name,
        Email $email,
        Balance $balance,
        ?int $id = null,
        ?Carbon $emailVerifiedAt = null,
        ?Carbon $createdAt = null,
        ?Carbon $updatedAt = null,
        ?Carbon $deletedAt = null
    ) {
        $this->validateName($name);
        
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->balance = $balance;
        $this->emailVerifiedAt = $emailVerifiedAt;
        $this->createdAt = $createdAt ?? Carbon::now();
        $this->updatedAt = $updatedAt ?? Carbon::now();
        $this->deletedAt = $deletedAt;
    }

    private function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('El nombre no puede estar vacío');
        }

        if (strlen($name) > 255) {
            throw new \InvalidArgumentException('El nombre no puede tener más de 255 caracteres');
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getBalance(): Balance
    {
        return $this->balance;
    }

    public function getEmailVerifiedAt(): ?Carbon
    {
        return $this->emailVerifiedAt;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?Carbon
    {
        return $this->deletedAt;
    }

    public function canAffordTransfer(Amount $amount): bool
    {
        return $this->balance->canAfford($amount);
    }

    public function debit(Amount $amount): void
    {
        if (!$this->canAffordTransfer($amount)) {
            throw new \InvalidArgumentException('Fondos insuficientes para realizar la transferencia');
        }
        
        $this->balance = $this->balance->subtract($amount);
        $this->updatedAt = Carbon::now();
    }

    public function credit(Amount $amount): void
    {
        $this->balance = $this->balance->add($amount);
        $this->updatedAt = Carbon::now();
    }

    public function updateName(string $name): void
    {
        $this->validateName($name);
        $this->name = $name;
        $this->updatedAt = Carbon::now();
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
        $this->emailVerifiedAt = null;
        $this->updatedAt = Carbon::now();
    }

    public function verifyEmail(): void
    {
        $this->emailVerifiedAt = Carbon::now();
        $this->updatedAt = Carbon::now();
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function softDelete(): void
    {
        $this->deletedAt = Carbon::now();
        $this->updatedAt = Carbon::now();
    }

    public function restore(): void
    {
        $this->deletedAt = null;
        $this->updatedAt = Carbon::now();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function isActive(): bool
    {
        return !$this->isDeleted();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email->getValue(),
            'balance' => $this->balance->getValue(),
            'email_verified_at' => $this->emailVerifiedAt?->toISOString(),
            'created_at' => $this->createdAt?->toISOString(),
            'updated_at' => $this->updatedAt?->toISOString(),
            'deleted_at' => $this->deletedAt?->toISOString(),
        ];
    }
}