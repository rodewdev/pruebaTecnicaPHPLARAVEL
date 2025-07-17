<?php

namespace App\Domain\Transaction\Repositories;

use App\Domain\Transaction\Entities\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface TransactionRepositoryInterface
{
    public function create(array $data): Transaction;
    
    public function findById(int $id): ?Transaction;
    
    public function getDailyTransferTotal(int $userId, Carbon $date): float;
    
    public function checkDuplicateTransaction(array $criteria): bool;
    
    public function getTransferTotalsByUser(): Collection;
    
    public function getAverageAmountByUser(): Collection;
    
    public function exportToCsv(array $filters = []): string;
    
    public function getTransactionsForUser(int $userId, int $perPage = 15): array;
    
    public function getTransactionsByDateRange(Carbon $startDate, Carbon $endDate): Collection;
    
    public function updateStatus(int $transactionId, string $status): bool;
}