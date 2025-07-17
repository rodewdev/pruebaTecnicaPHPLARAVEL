<?php

namespace Tests\Unit\Application\Services;

use App\Application\Services\TransactionService;
use App\Domain\Transaction\Exceptions\DailyLimitExceededException;
use App\Domain\Transaction\Exceptions\InsufficientFundsException;
use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Models\User as EloquentUser;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $transactionService;
    private UserRepositoryInterface $userRepository;
    private TransactionRepositoryInterface $transactionRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = app(UserRepositoryInterface::class);
        $this->transactionRepository = app(TransactionRepositoryInterface::class);
        $this->transactionService = new TransactionService(
            $this->transactionRepository,
            $this->userRepository
        );
    }

    public function test_transfer_money_successfully()
    {
        $sender = EloquentUser::create([
            'name' => 'Sender User',
            'email' => 'sender@test.com',
            'password' => bcrypt('password'),
            'balance' => 1000.00,
        ]);

        $receiver = EloquentUser::create([
            'name' => 'Receiver User',
            'email' => 'receiver@test.com',
            'password' => bcrypt('password'),
            'balance' => 500.00,
        ]);

        $transaction = $this->transactionService->transferMoney(
            $sender->id,
            $receiver->id,
            100.00,
            'Test transfer'
        );

        $this->assertNotNull($transaction);
        $this->assertEquals(100.00, $transaction->getAmount()->getValue());
        $this->assertEquals('Test transfer', $transaction->getDescription());
        
        $sender->refresh();
        $receiver->refresh();
        $this->assertEquals(900.00, $sender->balance);
        $this->assertEquals(600.00, $receiver->balance);
    }

    public function test_transfer_fails_with_insufficient_funds()
    {
        $sender = EloquentUser::create([
            'name' => 'Poor Sender',
            'email' => 'poor@test.com',
            'password' => bcrypt('password'),
            'balance' => 50.00,
        ]);

        $receiver = EloquentUser::create([
            'name' => 'Receiver User',
            'email' => 'receiver@test.com',
            'password' => bcrypt('password'),
            'balance' => 500.00,
        ]);

        $this->expectException(InsufficientFundsException::class);

        $this->transactionService->transferMoney(
            $sender->id,
            $receiver->id,
            100.00,
            'Should fail'
        );
    }

    public function test_daily_limit_is_enforced()
    {
        $sender = EloquentUser::create([
            'name' => 'Rich Sender',
            'email' => 'rich@test.com',
            'password' => bcrypt('password'),
            'balance' => 10000.00,
        ]);

        $receiver = EloquentUser::create([
            'name' => 'Receiver User',
            'email' => 'receiver@test.com',
            'password' => bcrypt('password'),
            'balance' => 500.00,
        ]);

        $this->transactionService->transferMoney(
            $sender->id,
            $receiver->id,
            3000.00,
            'First transfer'
        );

        $this->transactionService->transferMoney(
            $sender->id,
            $receiver->id,
            1500.00,
            'Second transfer'
        );

        $this->expectException(DailyLimitExceededException::class);
        
        $this->transactionService->transferMoney(
            $sender->id,
            $receiver->id,
            1000.00,
            'Should fail - exceeds daily limit'
        );
    }

    public function test_single_transfer_exceeding_daily_limit_fails()
    {
        $sender = EloquentUser::create([
            'name' => 'Very Rich Sender',
            'email' => 'veryrich@test.com',
            'password' => bcrypt('password'),
            'balance' => 10000.00,
        ]);

        $receiver = EloquentUser::create([
            'name' => 'Receiver User',
            'email' => 'receiver@test.com',
            'password' => bcrypt('password'),
            'balance' => 500.00,
        ]);

        $this->expectException(DailyLimitExceededException::class);

        $this->transactionService->transferMoney(
            $sender->id,
            $receiver->id,
            5500.00,
            'Should fail - single transfer exceeds limit'
        );
    }

    public function test_daily_limit_resets_next_day()
    {
        $sender = EloquentUser::create([
            'name' => 'Time Traveler',
            'email' => 'timetraveler@test.com',
            'password' => bcrypt('password'),
            'balance' => 15000.00,
        ]);

        $receiver = EloquentUser::create([
            'name' => 'Receiver User',
            'email' => 'receiver@test.com',
            'password' => bcrypt('password'),
            'balance' => 500.00,
        ]);

        $this->transactionService->transferMoney(
            $sender->id,
            $receiver->id,
            5000.00,
            'Max transfer today'
        );

        Carbon::setTestNow(Carbon::tomorrow());

        $transaction = $this->transactionService->transferMoney(
            $sender->id,
            $receiver->id,
            1000.00,
            'Transfer tomorrow'
        );

        $this->assertNotNull($transaction);
        $this->assertEquals(1000.00, $transaction->getAmount()->getValue());

        Carbon::setTestNow();
    }
}