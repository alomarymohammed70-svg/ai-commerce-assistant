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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('product_sources')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('external_id', 150);
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->string('brand', 150)->nullable();
            $table->text('url');
            $table->text('image_url')->nullable();
            $table->char('currency', 3)->default('USD');
            $table->decimal('price', 12, 4);
            $table->decimal('original_price', 12, 4)->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->boolean('is_available')->default(true);
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['source_id', 'external_id'], 'source_external_unique');
            $table->index('price');
            $table->index('rating');
            $table->index('reviews_count');
            $table->index('is_available');
            $table->index('created_at');
            $table->index(['category_id', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

