<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Transaction\Entities\Transaction as TransactionEntity;
use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use App\Domain\Transaction\ValueObjects\Amount;
use App\Domain\Transaction\ValueObjects\TransactionType;
use App\Models\Transaction as EloquentTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    private const CACHE_TTL = 1800;
    private const DAILY_LIMIT_CACHE_TTL = 3600;
    private const CACHE_PREFIX = 'transaction:';
    private const DAILY_LIMIT_PREFIX = 'daily_limit:';

    public function create(array $data): TransactionEntity
    {
        return DB::transaction(function () use ($data) {
            $eloquentTransaction = EloquentTransaction::create([
                'sender_id' => $data['sender_id'],
                'receiver_id' => $data['receiver_id'],
                'amount' => $data['amount'],
                'type' => $data['type'],
                'status' => $data['status'] ?? TransactionEntity::STATUS_PENDING,
                'reference' => $data['reference'],
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $transactionEntity = $this->mapToEntity($eloquentTransaction);
            
            $this->clearDailyLimitCache($data['sender_id'], Carbon::now());
            
            return $transactionEntity;
        });
    }

    public function findById(int $id): ?TransactionEntity
    {
        $cacheKey = self::CACHE_PREFIX . $id;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            $eloquentTransaction = EloquentTransaction::find($id);
            
            return $eloquentTransaction ? $this->mapToEntity($eloquentTransaction) : null;
        });
    }

    public function getDailyTransferTotal(int $userId, Carbon $date): float
    {
        $cacheKey = self::DAILY_LIMIT_PREFIX . $userId . ':' . $date->format('Y-m-d');
        
        return Cache::remember($cacheKey, self::DAILY_LIMIT_CACHE_TTL, function () use ($userId, $date) {
            return EloquentTransaction::where('sender_id', $userId)
                ->where('type', 'transfer')
                ->where('status', TransactionEntity::STATUS_COMPLETED)
                ->whereDate('created_at', $date)
                ->sum('amount');
        });
    }

    public function checkDuplicateTransaction(array $criteria): bool
    {
        $query = EloquentTransaction::where('sender_id', $criteria['sender_id'])
            ->where('receiver_id', $criteria['receiver_id'])
            ->where('amount', $criteria['amount'])
            ->where('type', $criteria['type']);

        if (isset($criteria['time_window'])) {
            $timeWindow = Carbon::now()->subMinutes($criteria['time_window']);
            $query->where('created_at', '>=', $timeWindow);
        }

        return $query->exists();
    }

    public function getTransferTotalsByUser(): Collection
    {
        $cacheKey = 'transfer_totals_by_user';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return DB::table('transactions')
                ->select('sender_id', DB::raw('SUM(amount) as total_transferred'))
                ->where('status', TransactionEntity::STATUS_COMPLETED)
                ->where('type', 'transfer')
                ->groupBy('sender_id')
                ->orderByDesc('total_transferred')
                ->get();
        });
    }

    public function getAverageAmountByUser(): Collection
    {
        $cacheKey = 'average_amount_by_user';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return DB::table('transactions')
                ->select('sender_id', DB::raw('AVG(amount) as average_amount'), DB::raw('COUNT(*) as transaction_count'))
                ->where('status', TransactionEntity::STATUS_COMPLETED)
                ->where('type', 'transfer')
                ->groupBy('sender_id')
                ->orderByDesc('average_amount')
                ->get();
        });
    }

    public function exportToCsv(array $filters = []): string
    {
        $query = EloquentTransaction::with(['sender', 'receiver']);

        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'ID',
            'Emisor',
            'Receptor',
            'Monto',
            'Tipo',
            'Estado',
            'Referencia',
            'Descripción',
            'Fecha'
        ];

        $csv = implode(';', $headers) . "\n";

        foreach ($transactions as $transaction) {
            $row = [
                $transaction->id,
                $transaction->sender->name ?? 'N/A',
                $transaction->receiver->name ?? 'N/A',
                number_format($transaction->amount, 2),
                $transaction->type,
                $transaction->status,
                $transaction->reference,
                $transaction->description ?? '',
                $transaction->created_at->format('Y-m-d H:i:s')
            ];
            
            $row = array_map(function ($field) {
                return str_replace(';', '\\;', $field);
            }, $row);
            
            $csv .= implode(';', $row) . "\n";
        }

        return $csv;
    }

    public function getTransactionsForUser(int $userId, int $perPage = 15): array
    {
        $paginator = EloquentTransaction::with(['sender', 'receiver'])
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                      ->orWhere('receiver_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    public function getTransactionsByDateRange(Carbon $startDate, Carbon $endDate): Collection
    {
        return EloquentTransaction::with(['sender', 'receiver'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updateStatus(int $transactionId, string $status): bool
    {
        return DB::transaction(function () use ($transactionId, $status) {
            $result = EloquentTransaction::where('id', $transactionId)
                ->update(['status' => $status, 'updated_at' => now()]);
            
            if ($result) {

                $this->clearTransactionCache($transactionId);
                
                $this->clearReportCaches();
            }
            
            return $result > 0;
        });
    }

    private function mapToEntity(EloquentTransaction $eloquentTransaction): TransactionEntity
    {
        return new TransactionEntity(
            senderId: $eloquentTransaction->sender_id,
            receiverId: $eloquentTransaction->receiver_id,
            amount: new Amount($eloquentTransaction->amount),
            type: new TransactionType($eloquentTransaction->type),
            reference: $eloquentTransaction->reference,
            description: $eloquentTransaction->description,
            metadata: $eloquentTransaction->metadata,
            status: $eloquentTransaction->status,
            id: $eloquentTransaction->id,
            createdAt: $eloquentTransaction->created_at ? Carbon::parse($eloquentTransaction->created_at) : null,
            updatedAt: $eloquentTransaction->updated_at ? Carbon::parse($eloquentTransaction->updated_at) : null
        );
    }

    private function clearDailyLimitCache(int $userId, Carbon $date): void
    {
        $cacheKey = self::DAILY_LIMIT_PREFIX . $userId . ':' . $date->format('Y-m-d');
        Cache::forget($cacheKey);
    }

    private function clearTransactionCache(int $transactionId): void
    {
        $cacheKey = self::CACHE_PREFIX . $transactionId;
        Cache::forget($cacheKey);
    }

    private function clearReportCaches(): void
    {
        Cache::forget('transfer_totals_by_user');
        Cache::forget('average_amount_by_user');
    }
}