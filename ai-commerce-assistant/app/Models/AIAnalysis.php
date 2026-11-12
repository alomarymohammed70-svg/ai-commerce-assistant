<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIAnalysis extends Model
{
    use HasFactory;

    protected $table = 'ai_analyses';

    protected $fillable = [
        'canonical_product_id',
        'product_listing_id',
        'user_id',
        'provider',
        'model',
        'prompt_version',
        'decision',
        'opportunity_score',
        'risk_score',
        'confidence',
        'deterministic_metrics',
        'context_snapshot',
        'ai_response',
        'duration_ms',
        'tokens_used',
    ];

    protected function casts(): array
    {
        return [
            'opportunity_score' => 'float',
            'risk_score' => 'float',
            'confidence' => 'float',
            'duration_ms' => 'float',
            'tokens_used' => 'integer',
            'deterministic_metrics' => 'array',
            'context_snapshot' => 'array',
            'ai_response' => 'array',
        ];
    }

    /**
     * Associated Canonical Product.
     */
    public function canonicalProduct(): BelongsTo
    {
        return $this->belongsTo(CanonicalProduct::class, 'canonical_product_id');
    }

    /**
     * Associated specific Product Listing offer, if analyzed individually.
     */
    public function productListing(): BelongsTo
    {
        return $this->belongsTo(ProductListing::class, 'product_listing_id');
    }

    /**
     * Requesting user, if authenticated.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

