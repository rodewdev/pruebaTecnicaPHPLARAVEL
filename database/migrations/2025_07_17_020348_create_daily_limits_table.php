<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->decimal('total_transferred', 15, 2)->default(0.00);
            $table->integer('transaction_count')->default(0);
            $table->timestamps();
            
            // Índices únicos y optimizados para consultas rápidas de límites
            $table->unique(['user_id', 'date'], 'unique_user_date');
            $table->index('date', 'idx_daily_limits_date');
            $table->index('user_id', 'idx_daily_limits_user_id');
            $table->index(['user_id', 'date', 'total_transferred'], 'idx_daily_limits_user_date_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_limits');
    }
};
