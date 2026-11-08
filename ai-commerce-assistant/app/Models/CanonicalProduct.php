<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CanonicalProduct extends Model
{
    use HasFactory;

    protected $table = 'canonical_products';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'brand',
        'model_number',
        'description',
        'base_currency',
    ];

    /**
     * Assigned primary Category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * All marketplace listings / offers across Amazon, Alibaba, AliExpress, eBay, etc.
     */
    public function listings(): HasMany
    {
        return $this->hasMany(ProductListing::class, 'canonical_product_id');
    }

    /**
     * Available marketplace listings only.
     */
    public function activeListings(): HasMany
    {
        return $this->hasMany(ProductListing::class, 'canonical_product_id')
            ->where('is_available', true);
    }

    /**
     * Historical analysis metrics.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(ProductMetric::class, 'canonical_product_id');
    }

    /**
     * Latest calculated metric snapshot.
     */
    public function latestMetric(): HasOne
    {
        return $this->hasOne(ProductMetric::class, 'canonical_product_id')
            ->latestOfMany('calculated_at');
    }

    /**
     * Scope only canonical products having at least one active listing.
     */
    public function scopeWithActiveListings(Builder $query): Builder
    {
        return $query->whereHas('activeListings');
    }

    /**
     * Scope by brand.
     */
    public function scopeByBrand(Builder $query, ?string $brand): Builder
    {
        if (! empty($brand)) {
            $query->where('brand', 'LIKE', '%' . trim($brand) . '%');
        }

        return $query;
    }

    /**
     * All AI analyses associated with this product.
     */
    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AIAnalysis::class, 'canonical_product_id');
    }

    /**
     * Latest AI analysis record.
     */
    public function latestAIAnalysis(): HasOne
    {
        return $this->hasOne(AIAnalysis::class, 'canonical_product_id')
            ->latestOfMany('created_at');
    }
}

