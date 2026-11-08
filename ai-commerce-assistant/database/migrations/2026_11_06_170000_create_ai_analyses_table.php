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
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canonical_product_id')->nullable()->constrained('canonical_products')->cascadeOnDelete();
            $table->foreignId('product_listing_id')->nullable()->constrained('product_listings')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Provider & Model tracking
            $table->string('provider', 50)->index(); // e.g. 'openai', 'mock'
            $table->string('model', 100)->index(); // e.g. 'gpt-4o-mini', 'mock-commerce-v1'
            $table->string('prompt_version', 20)->default('v1')->index();
            
            // Primary recommendation outcomes
            $table->string('decision', 30)->index(); // 'BUY', 'WATCH', 'AVOID', 'INSUFFICIENT_DATA'
            $table->decimal('opportunity_score', 5, 2)->nullable();
            $table->decimal('risk_score', 5, 2)->nullable();
            $table->decimal('confidence', 3, 2)->nullable(); // 0.00 to 1.00
            
            // Detailed payloads & snapshots
            $table->json('deterministic_metrics'); // Calculated in Laravel via BCMath
            $table->json('context_snapshot'); // Input evidence passed to AI with Data Truth tags
            $table->json('ai_response'); // Validated structured AI response
            
            // Observability
            $table->decimal('duration_ms', 8, 2)->nullable();
            $table->integer('tokens_used')->nullable();
            
            $table->timestamps();

            $table->index(['canonical_product_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_analyses');
    }
};

