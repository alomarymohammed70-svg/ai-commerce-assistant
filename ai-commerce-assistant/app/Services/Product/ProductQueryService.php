<?php

namespace App\Services\Product;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ProductQueryService
{
    /**
     * Allowed columns for sorting to prevent SQL injection.
     */
    protected const ALLOWED_SORT_COLUMNS = [
        'created_at',
        'price',
        'rating',
        'reviews_count',
        'title',
        'brand',
    ];

    /**
     * Query products with sanitized filters and eager loading.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['source', 'category', 'latestMetric']);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage)); // Bound between 1 and 100

        return $query->paginate($perPage);
    }

    /**
     * Apply whitelisted filter criteria.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // Keyword Search (sanitized via Parameter Binding)
        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $subQuery) use ($term) {
                $subQuery->where('title', 'LIKE', '%' . $term . '%')
                    ->orWhere('brand', 'LIKE', '%' . $term . '%')
                    ->orWhere('external_id', 'LIKE', '%' . $term . '%');
            });
        }

        // Filter by Brand
        if (! empty($filters['brand'])) {
            $query->where('brand', 'LIKE', '%' . trim((string) $filters['brand']) . '%');
        }

        // Filter by Source (code or ID)
        if (! empty($filters['source'])) {
            $sourceVal = trim((string) $filters['source']);
            $query->whereHas('source', function (Builder $q) use ($sourceVal) {
                if (is_numeric($sourceVal)) {
                    $q->where('id', (int) $sourceVal);
                } else {
                    $q->where('code', strtolower($sourceVal));
                }
            });
        }

        // Filter by Category (slug or ID)
        if (! empty($filters['category'])) {
            $catVal = trim((string) $filters['category']);
            $query->whereHas('category', function (Builder $q) use ($catVal) {
                if (is_numeric($catVal)) {
                    $q->where('id', (int) $catVal);
                } else {
                    $q->where('slug', strtolower($catVal));
                }
            });
        }

        // Filter by Price range
        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        // Filter by Minimum Rating
        if (isset($filters['min_rating']) && is_numeric($filters['min_rating'])) {
            $query->where('rating', '>=', (float) $filters['min_rating']);
        }

        // Filter by Currency
        if (! empty($filters['currency'])) {
            $query->where('currency', strtoupper(trim((string) $filters['currency'])));
        }

        // Filter by Availability
        if (isset($filters['availability'])) {
            $isAvailable = filter_var($filters['availability'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isAvailable !== null) {
                $query->where('is_available', $isAvailable);
            }
        }
    }

    /**
     * Apply strict whitelisted sorting.
     */
    protected function applySorting(Builder $query, array $filters): void
    {
        $sortBy = strtolower(trim((string) ($filters['sort_by'] ?? 'created_at')));
        $direction = strtolower(trim((string) ($filters['sort_direction'] ?? 'desc')));

        if (! in_array($sortBy, self::ALLOWED_SORT_COLUMNS, true)) {
            throw new InvalidArgumentException("Unsupported sort_by field [{$sortBy}] for product listings.");
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException("Unsupported sort_direction [{$direction}].");
        }

        $query->orderBy($sortBy, $direction)->orderBy('id', $direction);
    }
}

