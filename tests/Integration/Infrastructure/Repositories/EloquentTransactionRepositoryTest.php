<?php

namespace Tests\Integration\Infrastructure\Repositories;

use App\Domain\Transaction\Entities\Transaction as TransactionEntity;
use App\Domain\Transaction\ValueObjects\Amount;
use App\Domain\Transaction\ValueObjects\TransactionType;
use App\Infrastructure\Repositories\EloquentTransactionRepository;
use App\Models\Transaction as EloquentTransaction;
use App\Models\User as EloquentUser;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EloquentTransactionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentTransactionRepository $repository;
    private EloquentUser $sender;
    private EloquentUser $receiver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentTransactionRepository();

        $this->sender = EloquentUser::factory()->create(['balance' => 1000.00]);
        $this->receiver = EloquentUser::factory()->create(['balance' => 500.00]);
    }

    public function test_can_create_transaction()
    {
        $transactionData = [
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 100.00,
            'type' => 'transfer',
            'reference' => 'TXN-123456',
            'description' => 'Test transaction',
        ];

        $transaction = $this->repository->create($transactionData);

        $this->assertEquals($this->sender->id, $transaction->getSenderId());
        $this->assertEquals($this->receiver->id, $transaction->getReceiverId());
        $this->assertEquals(100.00, $transaction->getAmount()->getValue());
        $this->assertTrue($transaction->getType()->isTransfer());
        $this->assertEquals('TXN-123456', $transaction->getReference());
        $this->assertEquals('Test transaction', $transaction->getDescription());
        $this->assertEquals(TransactionEntity::STATUS_PENDING, $transaction->getStatus());

        $this->assertDatabaseHas('transactions', [
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 100.00,
            'type' => 'transfer',
            'reference' => 'TXN-123456',
        ]);
    }

    public function test_can_find_transaction_by_id()
    {
        $eloquentTransaction = EloquentTransaction::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 200.00,
            'type' => 'transfer',
            'reference' => 'TXN-789012',
        ]);

        $transaction = $this->repository->findById($eloquentTransaction->id);

        $this->assertNotNull($transaction);
        $this->assertEquals($this->sender->id, $transaction->getSenderId());
        $this->assertEquals($this->receiver->id, $transaction->getReceiverId());
        $this->assertEquals(200.00, $transaction->getAmount()->getValue());
        $this->assertEquals('TXN-789012', $transaction->getReference());
    }

    public function test_returns_null_when_transaction_not_found()
    {
        $transaction = $this->repository->findById(999);

        $this->assertNull($transaction);
    }

    public function test_can_get_daily_transfer_total()
    {
        $today = Carbon::now();

        EloquentTransaction::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 100.00,
            'type' => 'transfer',
            'status' => TransactionEntity::STATUS_COMPLETED,
            'created_at' => $today,
        ]);

        EloquentTransaction::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 150.00,
            'type' => 'transfer',
            'status' => TransactionEntity::STATUS_COMPLETED,
            'created_at' => $today,
        ]);

        EloquentTransaction::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 50.00,
            'type' => 'transfer',
            'status' => TransactionEntity::STATUS_PENDING,
            'created_at' => $today,
        ]);

        $total = $this->repository->getDailyTransferTotal($this->sender->id, $today);

        $this->assertEquals(250.00, $total);
    }

    public function test_can_check_duplicate_transaction()
    {
        EloquentTransaction::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 100.00,
            'type' => 'transfer',
            'created_at' => Carbon::now()->subMinutes(2),
        ]);

        $criteria = [
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 100.00,
            'type' => 'transfer',
            'time_window' => 5, // 5 minutes
        ];

        $isDuplicate = $this->repository->checkDuplicateTransaction($criteria);

        $this->assertTrue($isDuplicate);
    }

    public function test_can_get_transfer_totals_by_user()
    {
        EloquentTransaction::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 100.00,
            'type' => 'transfer',
            'status' => TransactionEntity::STATUS_COMPLETED,
        ]);

        EloquentTransaction::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 200.00,
            'type' => 'transfer',
            'status' => TransactionEntity::STATUS_COMPLETED,
        ]);

        $totals = $this->repository->getTransferTotalsByUser();

        $this->assertNotEmpty($totals);
        $senderTotal = $totals->where('sender_id', $this->sender->id)->first();
        $this->assertEquals(300.00, $senderTotal->total_transferred);
    }

    public function test_can_get_average_amount_by_user()
    {
        EloquentTransaction::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 100.00,
            'type' => 'transfer',
            'status' => TransactionEntity::STATUS_COMPLETED,
        ]);

        EloquentTransaction::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 200.00,
            'type' => 'transfer',
            'status' => TransactionEntity::STATUS_COMPLETED,
        ]);

        $averages = $this->repository->getAverageAmountByUser();

        $this->assertNotEmpty($averages);
        $senderAverage = $averages->where('sender_id', $this->sender->id)->first();
        $this->assertEquals(150.00, $senderAverage->average_amount);
        $this->assertEquals(2, $senderAverage->transaction_count);
    }

    public function test_can_export_to_csv()
    {
        EloquentTransaction::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 100.00,
            'type' => 'transfer',
            'status' => TransactionEntity::STATUS_COMPLETED,
            'reference' => 'TXN-CSV-001',
            'description' => 'CSV test transaction',
        ]);

        $csv = $this->repository->exportToCsv();

        $this->assertStringContainsString('ID;Emisor;Receptor;Monto;Tipo;Estado;Referencia;Descripción;Fecha', $csv);
        $this->assertStringContainsString('TXN-CSV-001', $csv);
        $this->assertStringContainsString('100.00', $csv);
        $this->assertStringContainsString('transfer', $csv);
    }

    public function test_can_get_transactions_for_user()
    {
        EloquentTransaction::factory()->count(3)->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
        ]);

        EloquentTransaction::factory()->count(2)->create([
            'sender_id' => $this->receiver->id,
            'receiver_id' => $this->sender->id,
        ]);

        $result = $this->repository->getTransactionsForUser($this->sender->id, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(5, $result['total']);
    }

    public function test_can_update_transaction_status()
    {
        $eloquentTransaction = EloquentTransaction::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'status' => TransactionEntity::STATUS_PENDING,
        ]);

        $result = $this->repository->updateStatus($eloquentTransaction->id, TransactionEntity::STATUS_COMPLETED);

        $this->assertTrue($result);

        $this->assertDatabaseHas('transactions', [
            'id' => $eloquentTransaction->id,
            'status' => TransactionEntity::STATUS_COMPLETED,
        ]);
    }

    public function test_caches_daily_transfer_total()
    {
        Cache::flush();

        $today = Carbon::now();
        EloquentTransaction::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 100.00,
            'type' => 'transfer',
            'status' => TransactionEntity::STATUS_COMPLETED,
            'created_at' => $today,
        ]);

        $total1 = $this->repository->getDailyTransferTotal($this->sender->id, $today);

        $total2 = $this->repository->getDailyTransferTotal($this->sender->id, $today);

        $this->assertEquals($total1, $total2);

        $cacheKey = 'daily_limit:' . $this->sender->id . ':' . $today->format('Y-m-d');
        $this->assertNotNull(Cache::get($cacheKey));
    }

    public function test_clears_cache_after_creating_transaction()
    {
        $today = Carbon::now();

        $this->repository->getDailyTransferTotal($this->sender->id, $today);
        $cacheKey = 'daily_limit:' . $this->sender->id . ':' . $today->format('Y-m-d');
        $this->assertNotNull(Cache::get($cacheKey));

        $this->repository->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'amount' => 100.00,
            'type' => 'transfer',
            'reference' => 'TXN-CACHE-TEST',
        ]);

        $this->assertNull(Cache::get($cacheKey));
    }
}
