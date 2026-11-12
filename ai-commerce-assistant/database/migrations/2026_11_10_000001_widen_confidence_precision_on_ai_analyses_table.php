<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen confidence column from decimal(3,2) to decimal(5,4)
     * so normalized [0.0000..1.0000] confidence scores (e.g. 0.875)
     * retain precision cleanly between POST responses and GET persistence.
     */
    public function up(): void
    {
        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->decimal('confidence', 5, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->decimal('confidence', 3, 2)->nullable()->change();
        });
    }
};

