<?php

namespace App\Services\Product;

use App\Models\CanonicalProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class CanonicalProductQueryService
{
    protected const ALLOWED_SORT_COLUMNS = [
        'created_at',
        'name',
        'brand',
        'price',
        'rating',
        'reviews_count',
    ];

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = CanonicalProduct::query()
            ->with([
                'category',
                'latestMetric',
                'listings.source',
            ]);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage));

        return $query->paginate($perPage);
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        // Keyword Search (name, brand, model_number)
        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $subQuery) use ($term) {
                $subQuery->where('name', 'LIKE', '%' . $term . '%')
                    ->orWhere('brand', 'LIKE', '%' . $term . '%')
                    ->orWhere('model_number', 'LIKE', '%' . $term . '%')
                    ->orWhereHas('listings', function (Builder $q) use ($term) {
                        $q->where('external_id', 'LIKE', '%' . $term . '%')
                            ->orWhere('title', 'LIKE', '%' . $term . '%');
                    });
            });
        }

        // Filter by Brand
        if (! empty($filters['brand'])) {
            $query->where('brand', 'LIKE', '%' . trim((string) $filters['brand']) . '%');
        }

        // Filter by Category (slug or ID)
        if (! empty($filters['category'])) {
            $catVal = trim((string) $filters['category']);
            $query->where(function (Builder $sub) use ($catVal) {
                $sub->whereHas('category', function (Builder $q) use ($catVal) {
                    if (is_numeric($catVal)) {
                        $q->where('id', (int) $catVal);
                    } else {
                        $q->where('slug', strtolower($catVal));
                    }
                })->orWhereHas('listings.category', function (Builder $q) use ($catVal) {
                    if (is_numeric($catVal)) {
                        $q->where('id', (int) $catVal);
                    } else {
                        $q->where('slug', strtolower($catVal));
                    }
                });
            });
        }

        // Filter by Source (has active listing in source)
        if (! empty($filters['source'])) {
            $sourceVal = trim((string) $filters['source']);
            $query->whereHas('listings.source', function (Builder $q) use ($sourceVal) {
                if (is_numeric($sourceVal)) {
                    $q->where('id', (int) $sourceVal);
                } else {
                    $q->where('code', strtolower($sourceVal));
                }
            });
        }

        // Filter by Price range on listings
        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $minPrice = (float) $filters['min_price'];
            $query->whereHas('listings', function (Builder $q) use ($minPrice) {
                $q->where('price', '>=', $minPrice);
            });
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $maxPrice = (float) $filters['max_price'];
            $query->whereHas('listings', function (Builder $q) use ($maxPrice) {
                $q->where('price', '<=', $maxPrice);
            });
        }

        // Filter by Minimum Rating on listings
        if (isset($filters['min_rating']) && is_numeric($filters['min_rating'])) {
            $minRating = (float) $filters['min_rating'];
            $query->whereHas('listings', function (Builder $q) use ($minRating) {
                $q->where('rating', '>=', $minRating);
            });
        }

        // Filter by Base Currency
        if (! empty($filters['currency'])) {
            $query->where('base_currency', strtoupper(trim((string) $filters['currency'])));
        }

        // Filter by Availability on listings
        if (isset($filters['availability'])) {
            $isAvailable = filter_var($filters['availability'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isAvailable === true) {
                $query->whereHas('listings', function (Builder $q) {
                    $q->where('is_available', true);
                });
            } elseif ($isAvailable === false) {
                $query->whereDoesntHave('listings', function (Builder $q) {
                    $q->where('is_available', true);
                });
            }
        }
    }

    protected function applySorting(Builder $query, array $filters): void
    {
        $sortBy = strtolower(trim((string) ($filters['sort_by'] ?? 'created_at')));
        $direction = strtolower(trim((string) ($filters['sort_direction'] ?? 'desc')));

        if (! in_array($sortBy, self::ALLOWED_SORT_COLUMNS, true)) {
            throw new InvalidArgumentException("Unsupported sort_by field [{$sortBy}] for canonical products.");
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException("Unsupported sort_direction [{$direction}].");
        }

        match ($sortBy) {
            'price' => $query
                ->withMin([
                    'listings as sort_min_price' => fn (Builder $q) => $q->where('is_available', true),
                ], 'price')
                ->orderBy('sort_min_price', $direction),
            'rating' => $query
                ->withAvg('listings as sort_avg_rating', 'rating')
                ->orderBy('sort_avg_rating', $direction),
            'reviews_count' => $query
                ->withSum('listings as sort_total_reviews', 'reviews_count')
                ->orderBy('sort_total_reviews', $direction),
            default => $query->orderBy("canonical_products.{$sortBy}", $direction),
        };

        $query->orderBy('canonical_products.id', $direction);
    }
}
