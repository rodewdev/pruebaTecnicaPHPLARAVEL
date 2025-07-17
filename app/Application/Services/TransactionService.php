<?php

namespace App\Application\Services;

use App\Domain\Transaction\Entities\Transaction;
use App\Domain\Transaction\Exceptions\DailyLimitExceededException;
use App\Domain\Transaction\Exceptions\DuplicateTransactionException;
use App\Domain\Transaction\Exceptions\InsufficientFundsException;
use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use App\Domain\Transaction\ValueObjects\Amount;
use App\Domain\Transaction\ValueObjects\TransactionType;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Repositories\UserRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    private const DAILY_LIMIT = 5000.00;

    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
        private UserRepositoryInterface $userRepository
    ) {}

    public function transferMoney(int $senderId, int $receiverId, float $amount, ?string $description = null): Transaction
    {
        return DB::transaction(function () use ($senderId, $receiverId, $amount, $description) {

            $senderModel = \App\Models\User::where('id', $senderId)->lockForUpdate()->first();
            $receiverModel = \App\Models\User::where('id', $receiverId)->lockForUpdate()->first();

            if (!$senderModel || $senderModel->deleted_at) {
                throw new UserNotFoundException('Usuario emisor no encontrado');
            }

            if (!$receiverModel || $receiverModel->deleted_at) {
                throw new UserNotFoundException('Usuario receptor no encontrado');
            }

            $sender = $this->userRepository->findById($senderId);
            $receiver = $this->userRepository->findById($receiverId);

            $transferAmount = new Amount($amount);

            if (!$sender->canAffordTransfer($transferAmount)) {
                throw new InsufficientFundsException();
            }

            $today = Carbon::now();
            $dailyTotal = $this->transactionRepository->getDailyTransferTotal($senderId, $today);

            Log::info('Verificación de límite diario', [
                'user_id' => $senderId,
                'current_total' => $dailyTotal,
                'transfer_amount' => $amount,
                'new_total' => $dailyTotal + $amount,
                'limit' => self::DAILY_LIMIT,
                'date' => $today->format('Y-m-d H:i:s'),
                'timezone' => $today->getTimezone()->getName()
            ]);

            if (($dailyTotal + $amount) > self::DAILY_LIMIT) {
                Log::warning('Intento de exceder límite diario', [
                    'user_id' => $senderId,
                    'current_total' => $dailyTotal,
                    'attempted_amount' => $amount,
                    'would_be_total' => $dailyTotal + $amount,
                    'limit' => self::DAILY_LIMIT
                ]);
                throw new DailyLimitExceededException();
            }

            $duplicateCriteria = [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'type' => 'transfer',
                'time_window' => 5,
            ];

            if ($this->transactionRepository->checkDuplicateTransaction($duplicateCriteria)) {
                throw new DuplicateTransactionException();
            }

            $reference = Transaction::generateReference();
            $transactionData = [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'type' => 'transfer',
                'reference' => $reference,
                'description' => $description,
                'status' => Transaction::STATUS_PENDING,
            ];

            $transaction = $this->transactionRepository->create($transactionData);

            $this->userRepository->updateBalance($senderId, $sender->getBalance()->getValue() - $amount);
            $this->userRepository->updateBalance($receiverId, $receiver->getBalance()->getValue() + $amount);

            $this->transactionRepository->updateStatus($transaction->getId(), Transaction::STATUS_COMPLETED);

            \Illuminate\Support\Facades\Cache::forget("daily_limit:{$senderId}:" . $today->format('Y-m-d'));

            Log::info('Transferencia de dinero completada', [
                'transaction_id' => $transaction->getId(),
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'reference' => $reference,
                'new_daily_total' => $dailyTotal + $amount,
            ]);

            return $transaction;
        });
    }
}
