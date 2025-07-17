<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['transfer', 'deposit', 'withdrawal'])->default('transfer');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('reference')->unique();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Índices optimizados para consultas frecuentes
            $table->index('sender_id', 'idx_transactions_sender');
            $table->index('receiver_id', 'idx_transactions_receiver');
            $table->index('created_at', 'idx_transactions_created_at');
            $table->index('reference', 'idx_transactions_reference');
            $table->index(['status', 'type'], 'idx_transactions_status_type');
            $table->index(['sender_id', 'created_at'], 'idx_transactions_sender_date');
            $table->index(['receiver_id', 'created_at'], 'idx_transactions_receiver_date');
            $table->index(['type', 'status', 'created_at'], 'idx_transactions_type_status_date');
        });

        // Agregar CHECK constraints si el motor lo soporta
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_amount_positive CHECK (amount > 0)');
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_different_users CHECK (sender_id != receiver_id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar CHECK constraints si existen
        if (config('database.default') === 'mysql') {
            try {
                DB::statement('ALTER TABLE transactions DROP CONSTRAINT chk_amount_positive');
                DB::statement('ALTER TABLE transactions DROP CONSTRAINT chk_different_users');
            } catch (Exception $e) {
                // Ignorar si los constraints no existen
            }
        }
        
        Schema::dropIfExists('transactions');
    }
};
