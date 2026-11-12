<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow selling_price, estimated_profit, and profit_margin to store null when
     * no valid selling_price is available, rather than fabricating synthetic markup values or 0.
     */
    public function up(): void
    {
        Schema::table('product_metrics', function (Blueprint $table) {
            $table->decimal('selling_price', 12, 4)->nullable()->change();
            $table->decimal('estimated_profit', 12, 4)->nullable()->change();
            $table->decimal('profit_margin', 7, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_metrics', function (Blueprint $table) {
            $table->decimal('selling_price', 12, 4)->nullable(false)->change();
            $table->decimal('estimated_profit', 12, 4)->nullable(false)->change();
            $table->decimal('profit_margin', 7, 4)->nullable(false)->change();
        });
    }
};

