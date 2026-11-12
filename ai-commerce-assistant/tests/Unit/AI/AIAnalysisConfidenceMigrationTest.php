<?php

namespace Tests\Unit\AI;

use App\Models\AIAnalysis;
use App\Models\CanonicalProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AIAnalysisConfidenceMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_widen_confidence_migration_runs_up_and_down_cleanly_and_preserves_0_875(): void
    {
        $migration = require database_path('migrations/2026_11_10_000001_widen_confidence_precision_on_ai_analyses_table.php');

        $product = CanonicalProduct::create([
            'name' => 'Migration Check Product',
            'slug' => 'migration-check-product',
            'base_currency' => 'USD',
        ]);

        // Test reversible down and up without SQL errors
        $migration->down();
        $migration->up();

        $this->assertTrue(Schema::hasColumn('ai_analyses', 'confidence'));

        $columns = collect(Schema::getColumns('ai_analyses'))->keyBy('name');
        $this->assertTrue($columns->has('confidence'));
        $this->assertTrue((bool) $columns['confidence']['nullable']);

        $analysis = AIAnalysis::create([
            'canonical_product_id' => $product->id,
            'provider' => 'mock',
            'model' => 'mock-v1',
            'prompt_version' => 'v1',
            'decision' => 'BUY',
            'opportunity_score' => 82.5,
            'risk_score' => 24.5,
            'confidence' => 0.875,
            'deterministic_metrics' => [],
            'context_snapshot' => [],
            'ai_response' => [],
        ]);

        $reloaded = AIAnalysis::findOrFail($analysis->id);
        $this->assertIsFloat($reloaded->confidence);
        $this->assertSame(0.875, $reloaded->confidence);
        $this->assertIsFloat($reloaded->opportunity_score);
        $this->assertSame(82.5, $reloaded->opportunity_score);
        $this->assertIsFloat($reloaded->risk_score);
        $this->assertSame(24.5, $reloaded->risk_score);
    }
}

