<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductListing extends Model
{
    use HasFactory;

    protected $table = 'product_listings';

    protected $fillable = [
        'canonical_product_id',
        'source_id',
        'category_id',
        'external_id',
        'title',
        'description',
        'brand',
        'url',
        'image_url',
        'currency',
        'price',
        'original_price',
        'rating',
        'reviews_count',
        'is_available',
        'raw_payload',
        'observed_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'original_price' => 'decimal:4',
            'rating' => 'decimal:2',
            'reviews_count' => 'integer',
            'is_available' => 'boolean',
            'raw_payload' => 'array',
            'observed_at' => 'datetime',
        ];
    }

    /**
     * Parent canonical product.
     */
    public function canonicalProduct(): BelongsTo
    {
        return $this->belongsTo(CanonicalProduct::class, 'canonical_product_id');
    }

    /**
     * Marketplace source (Amazon, Alibaba, etc.).
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ProductSource::class, 'source_id');
    }

    /**
     * Source-specific category if any.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Analysis metrics for this listing.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(ProductMetric::class, 'product_id');
    }

    /**
     * Latest calculated metric for this listing visible to the current viewer (shared or own).
     */
    public function latestMetric(?int $viewerId = null): HasOne
    {
        $resolvedViewerId = func_num_args() > 0 ? $viewerId : ProductMetric::resolveApiViewerId();

        return $this->hasOne(ProductMetric::class, 'product_id')
            ->ofMany(
                ['calculated_at' => 'MAX', 'id' => 'MAX'],
                fn (Builder $query) => $query->visibleTo($resolvedViewerId)
            );
    }

    /**
     * Scope only available listings.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope by source code.
     */
    public function scopeBySource(Builder $query, string $sourceCode): Builder
    {
        return $query->whereHas('source', function (Builder $q) use ($sourceCode) {
            $q->where('code', strtolower(trim($sourceCode)));
        });
    }

    /**
     * Scope price range.
     */
    public function scopeInPriceRange(Builder $query, ?float $min, ?float $max): Builder
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }

        if ($max !== null) {
            $query->where('price', '<=', $max);
        }

        return $query;
    }

    /**
     * Scope minimum rating.
     */
    public function scopeWithMinimumRating(Builder $query, ?float $minRating): Builder
    {
        if ($minRating !== null) {
            $query->where('rating', '>=', $minRating);
        }

        return $query;
    }

    /**
     * All AI analyses associated with this listing.
     */
    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AIAnalysis::class, 'product_listing_id');
    }

    /**
     * Latest AI analysis record.
     */
    public function latestAIAnalysis(): HasOne
    {
        return $this->hasOne(AIAnalysis::class, 'product_listing_id')
            ->latestOfMany('created_at');
    }

    /**
     * Build a safe, non-sensitive seller summary strictly from observed raw metadata.
     * Returns null when no seller metadata exists instead of inventing placeholders.
     *
     * @return array{username: ?string, store_name: ?string, feedback_percentage: ?float, feedback_score: ?int}|null
     */
    public function extractSellerSummary(): ?array
    {
        $raw = is_array($this->raw_payload) ? $this->raw_payload : [];
        $sellerNode = is_array($raw['seller'] ?? null) ? $raw['seller'] : [];

        $username = $this->normalizeNullableString(
            $sellerNode['username']
            ?? $raw['seller_username']
            ?? $raw['seller_name']
            ?? $raw['supplier_name']
            ?? null
        );

        $storeName = $this->normalizeNullableString(
            $sellerNode['store_name']
            ?? $sellerNode['storeName']
            ?? $raw['store_name']
            ?? $raw['company_name']
            ?? null
        );

        $rawFeedbackPct = $sellerNode['feedbackPercentage']
            ?? $sellerNode['feedback_percentage']
            ?? $raw['seller_feedback_percentage']
            ?? $raw['positive_feedback_percent']
            ?? null;

        $feedbackPercentage = is_numeric($rawFeedbackPct)
            ? round((float) $rawFeedbackPct, 2)
            : null;

        $rawFeedbackScore = $sellerNode['feedbackScore']
            ?? $sellerNode['feedback_score']
            ?? $raw['seller_feedback_score']
            ?? $raw['feedback_score']
            ?? null;

        $feedbackScore = is_numeric($rawFeedbackScore)
            ? (int) $rawFeedbackScore
            : null;

        if (
            $username === null
            && $storeName === null
            && $feedbackPercentage === null
            && $feedbackScore === null
        ) {
            return null;
        }

        return [
            'username' => $username,
            'store_name' => $storeName,
            'feedback_percentage' => $feedbackPercentage,
            'feedback_score' => $feedbackScore,
        ];
    }

    protected function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}

