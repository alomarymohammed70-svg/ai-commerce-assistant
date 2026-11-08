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

        $calcSelling = (float) $deterministicMetrics['selling_price'];
        if ($calcSelling <= 0) {
            $missingData[] = 'verified_selling_price';
        }

        $hasVerifiedShipping = ! empty($financialInputs['shipping_cost']) && (float) $financialInputs['shipping_cost'] > 0;
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
                'selling_price' => [
                    'value' => $deterministicMetrics['selling_price'],
                    'truth' => DataTruthLevel::CALCULATED,
                ],
                'shipping_cost' => [
                    'value' => $deterministicMetrics['shipping_cost'],
                    'truth' => DataTruthLevel::ESTIMATED,
                    'is_verified' => $hasVerifiedShipping,
                    'note' => $hasVerifiedShipping ? 'User-supplied quote.' : 'Unverified baseline estimate; carrier rate pending.',
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
                'estimated_profit' => [
                    'value' => $deterministicMetrics['estimated_profit'],
                    'truth' => DataTruthLevel::CALCULATED,
                    'status' => $hasVerifiedShipping ? 'CALCULATED_CONFIRMED' : 'ESTIMATED_PENDING_SHIPPING',
                    'note' => 'selling_price - total_cost',
                ],
                'profit_margin_percent' => [
                    'value' => $deterministicMetrics['profit_margin'] . '%',
                    'truth' => DataTruthLevel::CALCULATED,
                ],
                'return_on_investment_percent' => [
                    'value' => $deterministicMetrics['return_on_investment'] . '%',
                    'truth' => DataTruthLevel::CALCULATED,
                ],
                'is_profitable' => [
                    'value' => $deterministicMetrics['is_profitable'],
                    'truth' => DataTruthLevel::CALCULATED,
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
        $sellingPrice = $inputs['selling_price'] ?? ($purchasePrice > 0 ? bcmul((string) $purchasePrice, '1.45', 4) : '0.0000');

        return [
            'currency' => $inputs['currency'] ?? $currency,
            'purchase_price' => $purchasePrice,
            'purchase_currency' => $inputs['purchase_currency'] ?? $currency,
            'selling_price' => $sellingPrice,
            'selling_currency' => $inputs['selling_currency'] ?? $currency,
            'shipping_cost' => $inputs['shipping_cost'] ?? 0.0,
            'shipping_currency' => $inputs['shipping_currency'] ?? $currency,
            'estimated_fees' => $inputs['estimated_fees'] ?? 0.0,
            'other_costs' => $inputs['other_costs'] ?? 0.0,
            'rating' => $rating,
            'reviews_count' => $reviewsCount,
        ];
    }
}

