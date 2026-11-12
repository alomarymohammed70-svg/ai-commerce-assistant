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
        Schema::table('product_metrics', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('canonical_product_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['canonical_product_id', 'user_id', 'calculated_at'], 'pm_canonical_user_calc_idx');
            $table->index(['product_id', 'user_id', 'calculated_at'], 'pm_listing_user_calc_idx');
            $table->index(['user_id', 'calculated_at'], 'pm_user_calc_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_metrics', function (Blueprint $table) {
            $table->dropIndex('pm_canonical_user_calc_idx');
            $table->dropIndex('pm_listing_user_calc_idx');
            $table->dropIndex('pm_user_calc_idx');
            $table->dropConstrainedForeignId('user_id');
        });
    }
};

