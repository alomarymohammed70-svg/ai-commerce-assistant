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
        Schema::create('product_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('selling_price', 12, 4);
            $table->decimal('purchase_price', 12, 4);
            $table->decimal('shipping_cost', 12, 4)->default(0.0000);
            $table->decimal('estimated_fees', 12, 4)->default(0.0000);
            $table->decimal('other_costs', 12, 4)->default(0.0000);
            $table->decimal('estimated_profit', 12, 4);
            $table->decimal('profit_margin', 7, 4);
            $table->decimal('return_on_investment', 8, 4)->nullable();
            $table->decimal('opportunity_score', 5, 2);
            $table->decimal('risk_score', 5, 2);
            $table->string('competition_level', 20)->default('medium');
            $table->json('calculation_notes')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['product_id', 'calculated_at']);
            $table->index('opportunity_score');
            $table->index('profit_margin');
            $table->index('risk_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_metrics');
    }
};

