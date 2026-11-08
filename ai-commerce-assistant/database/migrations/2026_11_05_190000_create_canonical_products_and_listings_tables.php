<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create canonical_products table
        Schema::create('canonical_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('brand', 150)->nullable();
            $table->string('model_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->char('base_currency', 3)->default('USD');
            $table->timestamps();

            $table->index('category_id');
            $table->index('brand');
            $table->index('model_number');
            $table->index('created_at');
        });

        // 2. Safely drop foreign key on product_metrics before renaming products table
        Schema::table('product_metrics', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        // 3. Rename products table to product_listings
        Schema::rename('products', 'product_listings');

        // 4. Add canonical_product_id and observed_at to product_listings
        Schema::table('product_listings', function (Blueprint $table) {
            $table->foreignId('canonical_product_id')
                ->nullable()
                ->after('id')
                ->constrained('canonical_products')
                ->nullOnDelete();
            $table->timestamp('observed_at')->nullable()->after('raw_payload');

            $table->index('canonical_product_id');
            $table->index('observed_at');
        });

        // 5. Re-link foreign key on product_metrics referencing product_listings and add canonical_product_id
        Schema::table('product_metrics', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('product_listings')->cascadeOnDelete();
            $table->foreignId('canonical_product_id')
                ->nullable()
                ->after('id')
                ->constrained('canonical_products')
                ->nullOnDelete();
            $table->index('canonical_product_id');
        });

        // 6. Data Migration: Automatically create CanonicalProduct for any existing listing
        $listings = DB::table('product_listings')->whereNull('canonical_product_id')->get();
        foreach ($listings as $listing) {
            $baseSlug = Str::slug($listing->title) ?: 'product';
            $uniqueSlug = $baseSlug . '-' . Str::lower(Str::random(5));

            $canonicalId = DB::table('canonical_products')->insertGetId([
                'category_id' => $listing->category_id,
                'name' => $listing->title,
                'slug' => $uniqueSlug,
                'brand' => $listing->brand,
                'description' => $listing->description,
                'base_currency' => $listing->currency ?: 'USD',
                'created_at' => $listing->created_at ?: now(),
                'updated_at' => $listing->updated_at ?: now(),
            ]);

            DB::table('product_listings')->where('id', $listing->id)->update([
                'canonical_product_id' => $canonicalId,
                'observed_at' => $listing->created_at ?: now(),
            ]);

            DB::table('product_metrics')->where('product_id', $listing->id)->update([
                'canonical_product_id' => $canonicalId,
            ]);
        }

        // 7. Backward-compatibility: create database VIEW 'products' referencing 'product_listings'
        DB::statement('CREATE VIEW products AS SELECT * FROM product_listings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 0. Drop backward-compatibility view
        DB::statement('DROP VIEW IF EXISTS products');

        // 1. Drop foreign keys on product_metrics
        Schema::table('product_metrics', function (Blueprint $table) {
            $table->dropForeign(['canonical_product_id']);
            $table->dropColumn('canonical_product_id');
            $table->dropForeign(['product_id']);
        });

        // 2. Drop canonical_product_id from product_listings
        Schema::table('product_listings', function (Blueprint $table) {
            $table->dropForeign(['canonical_product_id']);
            $table->dropColumn(['canonical_product_id', 'observed_at']);
        });

        // 3. Rename product_listings back to products
        Schema::rename('product_listings', 'products');

        // 4. Re-link foreign key on product_metrics
        Schema::table('product_metrics', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // 5. Drop canonical_products
        Schema::dropIfExists('canonical_products');
    }
};
