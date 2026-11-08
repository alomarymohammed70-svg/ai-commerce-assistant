<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSource extends Model
{
    use HasFactory;

    protected $table = 'product_sources';

    protected $fillable = [
        'code',
        'name',
        'base_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Marketplace listings for this source.
     */
    public function listings(): HasMany
    {
        return $this->hasMany(ProductListing::class, 'source_id');
    }

    /**
     * Backward-compatibility alias.
     */
    public function products(): HasMany
    {
        return $this->listings();
    }

    /**
     * Scope to only active sources.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
