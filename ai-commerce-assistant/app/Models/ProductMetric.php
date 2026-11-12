<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class ProductMetric extends Model
{
    use HasFactory;

    protected $table = 'product_metrics';

    protected $fillable = [
        'product_id',
        'canonical_product_id',
        'selling_price',
        'purchase_price',
        'shipping_cost',
        'estimated_fees',
        'other_costs',
        'estimated_profit',
        'profit_margin',
        'return_on_investment',
        'opportunity_score',
        'risk_score',
        'competition_level',
        'calculation_notes',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'selling_price' => 'decimal:4',
            'purchase_price' => 'decimal:4',
            'shipping_cost' => 'decimal:4',
            'estimated_fees' => 'decimal:4',
            'other_costs' => 'decimal:4',
            'estimated_profit' => 'decimal:4',
            'profit_margin' => 'decimal:4',
            'return_on_investment' => 'decimal:4',
            'opportunity_score' => 'decimal:2',
            'risk_score' => 'decimal:2',
            'calculation_notes' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    /**
     * Owning user if created by a personal financial analysis; null indicates shared baseline catalog metric.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Restrict metrics to shared baseline entries (user_id is null) or records owned by the viewer.
     */
    public function scopeVisibleTo(Builder $query, ?int $userId): Builder
    {
        $column = $query->qualifyColumn('user_id');

        return $query->where(function (Builder $sub) use ($column, $userId) {
            $sub->whereNull($column);
            if ($userId !== null) {
                $sub->orWhere($column, $userId);
            }
        });
    }

    /**
     * Determine whether a given viewer may see this metric on catalog/product endpoints.
     */
    public function isVisibleTo(?User $viewer): bool
    {
        if ($this->user_id === null) {
            return true;
        }

        return $viewer !== null && (int) $this->user_id === (int) $viewer->id;
    }

    /**
     * Resolve the authenticated API viewer (Sanctum first, stateless-safe).
     */
    public static function resolveApiViewer(?Request $request = null): ?User
    {
        $sanctumUser = $request?->user('sanctum') ?? auth('sanctum')->user();
        if ($sanctumUser instanceof User) {
            return $sanctumUser;
        }

        $fallback = $request?->user() ?? auth()->user();

        return $fallback instanceof User ? $fallback : null;
    }

    /**
     * Resolve the authenticated API viewer user ID, or null for anonymous guests.
     */
    public static function resolveApiViewerId(?Request $request = null): ?int
    {
        $viewer = static::resolveApiViewer($request);

        return $viewer !== null ? (int) $viewer->id : null;
    }

    /**
     * Parent canonical product.
     */
    public function canonicalProduct(): BelongsTo
    {
        return $this->belongsTo(CanonicalProduct::class, 'canonical_product_id');
    }

    /**
     * Associated marketplace listing.
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(ProductListing::class, 'product_id');
    }

    /**
     * Compatibility alias for listing.
     */
    public function product(): BelongsTo
    {
        return $this->listing();
    }
}
