<?php

namespace App\Domain\Transaction\Entities;

use App\Domain\Transaction\ValueObjects\Amount;
use App\Domain\Transaction\ValueObjects\TransactionType;
use Carbon\Carbon;

class Transaction
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    private const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
    ];

    private ?int $id;
    private int $senderId;
    private int $receiverId;
    private Amount $amount;
    private TransactionType $type;
    private string $status;
    private string $reference;
    private ?string $description;
    private ?array $metadata;
    private ?Carbon $createdAt;
    private ?Carbon $updatedAt;

    public function __construct(
        int $senderId,
        int $receiverId,
        Amount $amount,
        TransactionType $type,
        string $reference,
        ?string $description = null,
        ?array $metadata = null,
        string $status = self::STATUS_PENDING,
        ?int $id = null,
        ?Carbon $createdAt = null,
        ?Carbon $updatedAt = null
    ) {
        $this->validateSenderAndReceiver($senderId, $receiverId);
        $this->validateStatus($status);
        $this->validateReference($reference);
        
        $this->id = $id;
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->amount = $amount;
        $this->type = $type;
        $this->status = $status;
        $this->reference = $reference;
        $this->description = $description;
        $this->metadata = $metadata;
        $this->createdAt = $createdAt ?? Carbon::now();
        $this->updatedAt = $updatedAt ?? Carbon::now();
    }

    private function validateSenderAndReceiver(int $senderId, int $receiverId): void
    {
        if ($senderId === $receiverId) {
            throw new \InvalidArgumentException('El emisor y receptor no pueden ser el mismo usuario');
        }

        if ($senderId <= 0 || $receiverId <= 0) {
            throw new \InvalidArgumentException('Los IDs de emisor y receptor deben ser válidos');
        }
    }

    private function validateStatus(string $status): void
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(
                'Estado inválido. Estados válidos: ' . implode(', ', self::VALID_STATUSES)
            );
        }
    }

    private function validateReference(string $reference): void
    {
        if (empty(trim($reference))) {
            throw new \InvalidArgumentException('La referencia no puede estar vacía');
        }

        if (strlen($reference) > 255) {
            throw new \InvalidArgumentException('La referencia no puede tener más de 255 caracteres');
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSenderId(): int
    {
        return $this->senderId;
    }

    public function getReceiverId(): int
    {
        return $this->receiverId;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    public function markAsCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->updatedAt = Carbon::now();
    }

    public function markAsFailed(): void
    {
        $this->status = self::STATUS_FAILED;
        $this->updatedAt = Carbon::now();
    }

    public function updateDescription(string $description): void
    {
        $this->description = $description;
        $this->updatedAt = Carbon::now();
    }

    public function addMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata ?? [], $metadata);
        $this->updatedAt = Carbon::now();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isTransfer(): bool
    {
        return $this->type->isTransfer();
    }

    public function canBeProcessed(): bool
    {
        return $this->isPending() && $this->amount->isValidForTransfer();
    }

    public static function generateReference(): string
    {
        return 'TXN-' . strtoupper(uniqid()) . '-' . time();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'amount' => $this->amount->getValue(),
            'type' => $this->type->getValue(),
            'status' => $this->status,
            'reference' => $this->reference,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt?->toISOString(),
            'updated_at' => $this->updatedAt?->toISOString(),
        ];
    }
}