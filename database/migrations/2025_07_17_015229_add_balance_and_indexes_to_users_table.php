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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->default(0.00)->after('password');
            
            $table->softDeletes()->after('updated_at');
            
            $table->index('balance', 'idx_users_balance');
            $table->index('deleted_at', 'idx_users_deleted_at');
            $table->index(['email', 'deleted_at'], 'idx_users_email_deleted');
            $table->index(['balance', 'deleted_at'], 'idx_users_balance_deleted');
        });

        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE users ADD CONSTRAINT chk_balance_positive CHECK (balance >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (config('database.default') === 'mysql') {
                try {
                    DB::statement('ALTER TABLE users DROP CONSTRAINT chk_balance_positive');
                } catch (Exception $e) {
                }
            }
            
            $table->dropIndex('idx_users_balance');
            $table->dropIndex('idx_users_deleted_at');
            $table->dropIndex('idx_users_email_deleted');
            $table->dropIndex('idx_users_balance_deleted');
            
            $table->dropSoftDeletes();
            $table->dropColumn('balance');
        });
    }
};
