<?php

namespace Database\Seeders;

use App\Domain\Transaction\Entities\Transaction;
use App\Models\Transaction as EloquentTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar que existan usuarios
        $users = User::all();
        
        if ($users->count() < 3) {
            $this->command->info('No hay suficientes usuarios. Ejecuta primero UserSeeder.');
            return;
        }
        
        // Crear algunas transacciones de prueba
        $transactions = [
            [
                'sender_id' => 1,
                'receiver_id' => 2,
                'amount' => 100.00,
                'type' => 'transfer',
                'status' => Transaction::STATUS_COMPLETED,
                'reference' => Transaction::generateReference(),
                'description' => 'Pago por servicios',
            ],
            [
                'sender_id' => 2,
                'receiver_id' => 3,
                'amount' => 50.00,
                'type' => 'transfer',
                'status' => Transaction::STATUS_COMPLETED,
                'reference' => Transaction::generateReference(),
                'description' => 'Reembolso',
            ],
            [
                'sender_id' => 3,
                'receiver_id' => 1,
                'amount' => 75.00,
                'type' => 'transfer',
                'status' => Transaction::STATUS_COMPLETED,
                'reference' => Transaction::generateReference(),
                'description' => 'Pago de deuda',
            ],
            [
                'sender_id' => 1,
                'receiver_id' => 3,
                'amount' => 200.00,
                'type' => 'transfer',
                'status' => Transaction::STATUS_COMPLETED,
                'reference' => Transaction::generateReference(),
                'description' => 'Inversión',
            ],
            [
                'sender_id' => 2,
                'receiver_id' => 1,
                'amount' => 25.00,
                'type' => 'transfer',
                'status' => Transaction::STATUS_COMPLETED,
                'reference' => Transaction::generateReference(),
                'description' => 'Pago compartido',
            ],
        ];
        
        foreach ($transactions as $transaction) {
            EloquentTransaction::create($transaction);
        }
        
        $this->command->info('Se han creado 5 transacciones de prueba.');
    }
}