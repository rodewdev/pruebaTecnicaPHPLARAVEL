<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\Entities\User;
use App\Domain\User\ValueObjects\Email;

interface UserRepositoryInterface
{
    public function create(array $data): User;
    
    public function findById(int $id): ?User;
    
    public function findByEmail(Email $email): ?User;
    
    public function update(int $id, array $data): User;
    
    public function delete(int $id): bool;
    
    public function updateBalance(int $userId, float $amount): bool;
    
    public function existsByEmail(Email $email): bool;
    
    public function getAllPaginated(int $perPage = 15): array;
}