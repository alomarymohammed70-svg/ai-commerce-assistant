<?php

namespace App\Services\AI\Context;

use App\Models\CanonicalProduct;
use App\Models\ProductListing;
use App\Services\AI\DataTruthLevel;
use App\Services\Analysis\ProductAnalysisEngine;
use InvalidArgumentException;

class AIContextBuilder
{
    public function __construct(
        protected ProductAnalysisEngine $analysisEngine
    ) {}

    /**
     * Build structured AI Context with explicit Data Truth annotations.
     *
     * @param CanonicalProduct|ProductListing $subject
     * @param array<string, mixed> $financialInputs Optional user overrides for financial calculation
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function build(mixed $subject, array $financialInputs = []): array
    {
        $canonical = null;
        $activeListings = collect();
        $targetListing = null;

        if ($subject instanceof CanonicalProduct) {
            $canonical = $subject;
            $subject->loadMissing(['category', 'activeListings.source', 'activeListings.category']);
            $activeListings = $subject->activeListings;
        } elseif ($subject instanceof ProductListing) {
            $targetListing = $subject;
            $subject->loadMissing(['canonicalProduct.category', 'source', 'category']);
            $canonical = $subject->canonicalProduct;
            $activeListings = collect([$subject]);
        } else {
            throw new InvalidArgumentException('Subject must be an instance of CanonicalProduct or ProductListing.');
        }

        // 1. Run Deterministic Financial Math via ProductAnalysisEngine (BCMath)
        $hasExplicitSellingPrice = $this->hasExplicitSellingPrice($financialInputs);
        $financialParams = $this->prepareFinancialParams($subject, $financialInputs);
        $deterministicMetrics = $this->analysisEngine->calculate($financialParams);

        // 2. Format Market Offers with explicit Data Truth labels
        $offersContext = [];
        $missingData = [];

        foreach ($activeListings as $listing) {
            $raw = is_array($listing->raw_payload) ? $listing->raw_payload : [];
            $sellerUsername = $raw['seller']['username'] ?? $raw['seller_username'] ?? 'Third-party Seller';
            $sellerFeedbackPct = $raw['seller_feedback_percentage'] ?? $raw['seller']['feedbackPercentage'] ?? null;
            $sellerFeedbackScore = $raw['seller_feedback_score'] ?? $raw['seller']['feedbackScore'] ?? null;

            $offersContext[] = [
                'listing_id' => $listing->id,
                'external_id' => $listing->external_id,
                'source' => [
                    'value' => $listing->source?->code ?? 'unknown',
                    'truth' => DataTruthLevel::OBSERVED,
                ],
                'price' => [
                    'value' => (string) $listing->price,
                    'currency' => $listing->currency,
                    'truth' => DataTruthLevel::OBSERVED,
                ],
                'is_available' => [
                    'value' => (bool) $listing->is_available,
                    'truth' => DataTruthLevel::OBSERVED,
                ],
                'url' => [
                    'value' => $listing->url,
                    'truth' => DataTruthLevel::OBSERVED,
                ],
                // Strictly decoupled seller signals
                'seller_signals' => [
                    'supplier_username' => [
                        'value' => $sellerUsername,
                        'truth' => DataTruthLevel::OBSERVED,
                    ],
                    'seller_feedback_percentage' => [
                        'value' => $sellerFeedbackPct !== null ? (float) $sellerFeedbackPct : 'DATA_UNAVAILABLE',
                        'truth' => $sellerFeedbackPct !== null ? DataTruthLevel::OBSERVED : DataTruthLevel::OBSERVED,
                        'note' => 'Strictly seller reputation; NOT product catalog rating.',
                    ],
                    'seller_feedback_score' => [
                        'value' => $sellerFeedbackScore !== null ? (int) $sellerFeedbackScore : 'DATA_UNAVAILABLE',
                        'truth' => $sellerFeedbackScore !== null ? DataTruthLevel::OBSERVED : DataTruthLevel::OBSERVED,
                        'note' => 'Strictly seller feedback volume; NOT product sales volume.',
                    ],
                ],
                // Product-level reviews
                'product_reviews' => [
                    'rating' => [
                        'value' => $listing->rating !== null ? (float) $listing->rating : 'DATA_UNAVAILABLE',
                        'truth' => DataTruthLevel::OBSERVED,
                    ],
                    'reviews_count' => [
                        'value' => $listing->reviews_count > 0 ? (int) $listing->reviews_count : 'DATA_UNAVAILABLE',
                        'truth' => DataTruthLevel::OBSERVED,
                    ],
                ],
            ];
        }

        if (empty($offersContext)) {
            $missingData[] = 'active_marketplace_offers';
        }

        $calcPurchase = (float) $deterministicMetrics['purchase_price'];
        if ($calcPurchase <= 0) {
            $missingData[] = 'verified_purchase_price';
        }

        $calcSelling = $hasExplicitSellingPrice ? (float) $deterministicMetrics['selling_price'] : 0.0;
        if (! $hasExplicitSellingPrice || $calcSelling <= 0) {
            $missingData[] = 'verified_selling_price';
        }

        $observedVerifiedShipping = $this->extractVerifiedListingShipping($activeListings);
        $hasExplicitShippingInput = array_key_exists('shipping_cost', $financialInputs)
            && $financialInputs['shipping_cost'] !== null
            && $financialInputs['shipping_cost'] !== ''
            && is_numeric($financialInputs['shipping_cost']);
        $hasVerifiedShipping = $observedVerifiedShipping !== null
            && (! $hasExplicitShippingInput || abs((float) $financialInputs['shipping_cost'] - $observedVerifiedShipping) < 0.0001);
        if (! $hasVerifiedShipping) {
            $missingData[] = 'verified_shipping_cost_estimate';
        }

        // 3. Assemble Complete Structured AI Context
        return [
            'subject_type' => $subject instanceof CanonicalProduct ? 'canonical_product' : 'product_listing',
            'product' => [
                'id' => $canonical?->id ?? $targetListing?->id,
                'name' => [
                    'value' => $canonical?->name ?? $targetListing?->title ?? 'Untitled Product',
                    'truth' => DataTruthLevel::OBSERVED,
                ],
                'brand' => [
                    'value' => $canonical?->brand ?? $targetListing?->brand ?? 'DATA_UNAVAILABLE',
                    'truth' => DataTruthLevel::OBSERVED,
                ],
                'category' => [
                    'value' => $canonical?->category?->name ?? $targetListing?->category?->name ?? 'General Merchandise',
                    'truth' => DataTruthLevel::OBSERVED,
                ],
                'description' => [
                    'value' => $canonical?->description ?? $targetListing?->description ?? 'DATA_UNAVAILABLE',
                    'truth' => DataTruthLevel::OBSERVED,
                ],
            ],
            'market' => [
                'offers_count' => count($offersContext),
                'offers' => $offersContext,
            ],
            'financial_analysis' => [
                'currency' => [
                    'value' => $deterministicMetrics['currency'],
                    'truth' => DataTruthLevel::CALCULATED,
                ],
                'purchase_price' => [
                    'value' => $deterministicMetrics['purchase_price'],
                    'truth' => isset($financialInputs['purchase_price']) ? DataTruthLevel::ESTIMATED : DataTruthLevel::OBSERVED,
                ],
                'selling_price' => $hasExplicitSellingPrice
                    ? [
                        'value' => $deterministicMetrics['selling_price'],
                        'truth' => DataTruthLevel::CALCULATED,
                    ]
                    : [
                        'value' => null,
                        'truth' => null,
                        'status' => 'DATA_UNAVAILABLE',
                        'note' => 'Selling price was not provided; synthetic markup fallback is disabled.',
                    ],
                'shipping_cost' => [
                    'value' => $deterministicMetrics['shipping_cost'],
                    'truth' => $hasVerifiedShipping ? DataTruthLevel::OBSERVED : DataTruthLevel::ESTIMATED,
                    'is_verified' => $hasVerifiedShipping,
                    'note' => $hasVerifiedShipping
                        ? 'Observed verified marketplace/carrier shipping rate.'
                        : ($hasExplicitShippingInput
                            ? 'User-supplied estimate; verified carrier rate pending.'
                            : 'Unverified baseline estimate; carrier rate pending.'),
                ],
                'estimated_fees' => [
                    'value' => $deterministicMetrics['estimated_fees'],
                    'truth' => DataTruthLevel::ESTIMATED,
                ],
                'other_costs' => [
                    'value' => $deterministicMetrics['other_costs'],
                    'truth' => DataTruthLevel::ESTIMATED,
                ],
                'total_cost' => [
                    'value' => $deterministicMetrics['total_cost'],
                    'truth' => DataTruthLevel::CALCULATED,
                    'note' => 'Computed via Laravel BCMath precision.',
                ],
                'estimated_profit' => $hasExplicitSellingPrice
                    ? [
                        'value' => $deterministicMetrics['estimated_profit'],
                        'truth' => DataTruthLevel::CALCULATED,
                        'status' => $hasVerifiedShipping ? 'CALCULATED_CONFIRMED' : 'ESTIMATED_PENDING_SHIPPING',
                        'note' => 'selling_price - total_cost',
                    ]
                    : [
                        'value' => null,
                        'truth' => null,
                        'status' => 'INSUFFICIENT_DATA',
                        'note' => 'Cannot compute estimated profit without selling_price.',
                    ],
                'profit_margin_percent' => [
                    'value' => $hasExplicitSellingPrice
                        ? $this->toNullableFloat($deterministicMetrics['profit_margin'] ?? null)
                        : null,
                    'truth' => $hasExplicitSellingPrice ? DataTruthLevel::CALCULATED : null,
                ],
                'return_on_investment_percent' => [
                    'value' => $hasExplicitSellingPrice
                        ? $this->toNullableFloat($deterministicMetrics['return_on_investment'] ?? null)
                        : null,
                    'truth' => $hasExplicitSellingPrice ? DataTruthLevel::CALCULATED : null,
                ],
                'is_profitable' => [
                    'value' => $hasExplicitSellingPrice ? $deterministicMetrics['is_profitable'] : null,
                    'truth' => $hasExplicitSellingPrice ? DataTruthLevel::CALCULATED : null,
                ],
            ],
            'deterministic_heuristics' => [
                'opportunity_score' => [
                    'value' => (float) $deterministicMetrics['opportunity_score'],
                    'truth' => DataTruthLevel::HEURISTIC,
                ],
                'risk_score' => [
                    'value' => (float) $deterministicMetrics['risk_score'],
                    'truth' => DataTruthLevel::HEURISTIC,
                ],
                'competition_level' => [
                    'value' => $deterministicMetrics['competition_level'],
                    'truth' => DataTruthLevel::HEURISTIC,
                ],
            ],
            'data_truth_meta' => [
                'has_active_listings' => count($offersContext) > 0,
                'missing_critical_data' => $missingData,
                'assembled_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Resolve default and overridden financial parameters for calculation.
     *
     * @param CanonicalProduct|ProductListing $subject
     * @param array<string, mixed> $inputs
     * @return array<string, mixed>
     */
    protected function prepareFinancialParams(mixed $subject, array $inputs): array
    {
        $observedPrice = 0.0;
        $currency = 'USD';
        $rating = null;
        $reviewsCount = 0;

        if ($subject instanceof ProductListing) {
            $observedPrice = (float) $subject->price;
            $currency = $subject->currency ?: 'USD';
            $rating = $subject->rating;
            $reviewsCount = $subject->reviews_count;
        } elseif ($subject instanceof CanonicalProduct) {
            $cheapest = $subject->activeListings()->orderBy('price')->first();
            $observedPrice = $cheapest ? (float) $cheapest->price : 0.0;
            $currency = $subject->base_currency ?: ($cheapest?->currency ?? 'USD');
            $rating = $cheapest?->rating;
            $reviewsCount = $cheapest?->reviews_count ?? 0;
        }

        $purchasePrice = $inputs['purchase_price'] ?? $observedPrice;
        $sellingPrice = $this->hasExplicitSellingPrice($inputs) ? $inputs['selling_price'] : null;

        $observedShipping = $this->extractVerifiedListingShipping(
            $subject instanceof ProductListing
                ? collect([$subject])
                : ($subject instanceof CanonicalProduct ? $subject->activeListings : collect())
        );

        return [
            'currency' => $inputs['currency'] ?? $currency,
            'purchase_price' => $purchasePrice,
            'purchase_currency' => $inputs['purchase_currency'] ?? $currency,
            'selling_price' => $sellingPrice,
            'selling_currency' => $inputs['selling_currency'] ?? $currency,
            'shipping_cost' => $inputs['shipping_cost'] ?? $observedShipping ?? 0.0,
            'shipping_currency' => $inputs['shipping_currency'] ?? $currency,
            'estimated_fees' => $inputs['estimated_fees'] ?? 0.0,
            'other_costs' => $inputs['other_costs'] ?? 0.0,
            'rating' => $rating,
            'reviews_count' => $reviewsCount,
        ];
    }

    /**
     * Extract an observed, verified shipping rate from active listing payloads if present.
     * User-supplied/default numeric inputs alone must never self-verify shipping.
     */
    protected function extractVerifiedListingShipping(iterable $listings): ?float
    {
        foreach ($listings as $listing) {
            if (! $listing instanceof ProductListing || ! is_array($listing->raw_payload)) {
                continue;
            }

            $raw = $listing->raw_payload;
            $isVerified = ($raw['shipping_verified'] ?? $raw['shipping']['verified'] ?? false) === true;
            $rawCost = $raw['shipping_cost'] ?? $raw['shipping']['cost'] ?? null;

            if ($isVerified && $rawCost !== null && is_numeric($rawCost) && (float) $rawCost >= 0.0) {
                return (float) $rawCost;
            }
        }

        return null;
    }

    protected function hasExplicitSellingPrice(array $inputs): bool
    {
        return array_key_exists('selling_price', $inputs)
            && $inputs['selling_price'] !== null
            && $inputs['selling_price'] !== ''
            && is_numeric($inputs['selling_price']);
    }

    protected function toNullableFloat(mixed $value, int $precision = 4): ?float
    {
        if ($value === null || ! is_numeric($value)) {
            return null;
        }

        $float = (float) $value;

        return is_finite($float) ? round($float, $precision) : null;
    }
}

