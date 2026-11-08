<?php

namespace App\Services\Analysis;

use App\Contracts\Currency\CurrencyConverterInterface;
use App\Models\CanonicalProduct;
use App\Models\ProductListing;
use App\Models\ProductMetric;
use App\Services\Currency\StaticCurrencyConverter;
use InvalidArgumentException;

class ProductAnalysisEngine
{
    public function __construct(
        protected ?CurrencyConverterInterface $currencyConverter = null,
        protected ?OpportunityScoreEngine $opportunityScoreEngine = null
    ) {
        $this->currencyConverter = $currencyConverter ?? new StaticCurrencyConverter();
        $this->opportunityScoreEngine = $opportunityScoreEngine ?? new OpportunityScoreEngine();
    }

    /**
     * Perform deterministic calculations for a given set of input parameters.
     * Enforces strict currency normalization and safety: all amounts are converted
     * to the target calculation currency before computing financial totals.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function calculate(array $params): array
    {
        // 1. Establish calculation currency (default 'USD')
        $targetCurrency = strtoupper(trim((string) ($params['currency'] ?? $params['target_currency'] ?? 'USD')));
        $purchaseCurrency = strtoupper(trim((string) ($params['purchase_currency'] ?? $targetCurrency)));
        $sellingCurrency = strtoupper(trim((string) ($params['selling_currency'] ?? $targetCurrency)));
        $shippingCurrency = strtoupper(trim((string) ($params['shipping_currency'] ?? $targetCurrency)));

        $rawPurchasePrice = (float) ($params['purchase_price'] ?? 0.0);
        $rawSellingPrice = (float) ($params['selling_price'] ?? 0.0);
        $rawShippingCost = (float) ($params['shipping_cost'] ?? 0.0);
        $rawEstimatedFees = (float) ($params['estimated_fees'] ?? 0.0);
        $rawOtherCosts = (float) ($params['other_costs'] ?? 0.0);

        // 2. Strict Currency Normalization: Convert all components to target calculation currency
        $normalizedPurchase = $rawPurchasePrice;
        if ($purchaseCurrency !== $targetCurrency) {
            $normalizedPurchase = $this->currencyConverter->convert($rawPurchasePrice, $purchaseCurrency, $targetCurrency);
        }

        $normalizedSelling = $rawSellingPrice;
        if ($sellingCurrency !== $targetCurrency) {
            $normalizedSelling = $this->currencyConverter->convert($rawSellingPrice, $sellingCurrency, $targetCurrency);
        }

        $normalizedShipping = $rawShippingCost;
        if ($shippingCurrency !== $targetCurrency) {
            $normalizedShipping = $this->currencyConverter->convert($rawShippingCost, $shippingCurrency, $targetCurrency);
        }

        // Format as 4-decimal strings for BCMath precision
        $purchasePrice = $this->formatAmount($normalizedPurchase);
        $sellingPrice = $this->formatAmount($normalizedSelling);
        $shippingCost = $this->formatAmount($normalizedShipping);
        $estimatedFees = $this->formatAmount($rawEstimatedFees);
        $otherCosts = $this->formatAmount($rawOtherCosts);

        $rating = isset($params['rating']) ? (float) $params['rating'] : null;
        $reviewsCount = (int) ($params['reviews_count'] ?? 0);

        // 3. Deterministic Financial Math via BCMath
        // Total Cost = purchase + shipping + fees + other
        $totalCost = bcadd($purchasePrice, $shippingCost, 4);
        $totalCost = bcadd($totalCost, $estimatedFees, 4);
        $totalCost = bcadd($totalCost, $otherCosts, 4);

        // Estimated Net Profit = selling_price - total_cost
        $estimatedProfit = bcsub($sellingPrice, $totalCost, 4);

        // Profit Margin % = (Profit / Selling Price) * 100
        if (bccomp($sellingPrice, '0.0000', 4) <= 0) {
            $profitMargin = '0.0000';
        } else {
            $profitMargin = bcdiv(bcmul($estimatedProfit, '100.0000', 6), $sellingPrice, 4);
        }

        // Return on Investment % = (Profit / Total Cost) * 100
        if (bccomp($totalCost, '0.0000', 4) <= 0) {
            $roi = '0.0000';
        } else {
            $roi = bcdiv(bcmul($estimatedProfit, '100.0000', 6), $totalCost, 4);
        }

        // 4. Compute Opportunity Score using Strategy Engine
        $opportunityScore = $this->opportunityScoreEngine->compute(
            (float) $profitMargin,
            $rating,
            $reviewsCount,
            (float) $purchasePrice,
            $params
        );

        // 5. Deterministic Risk Score (0 to 100)
        $riskScore = $this->computeRiskScore(
            (float) $profitMargin,
            $rating,
            $reviewsCount,
            (float) $purchasePrice
        );

        // 6. Competition Level
        $competitionLevel = $this->computeCompetitionLevel($reviewsCount);

        return [
            'currency' => $targetCurrency,
            'purchase_price' => $purchasePrice,
            'original_purchase_price' => $this->formatAmount($rawPurchasePrice),
            'original_purchase_currency' => $purchaseCurrency,
            'selling_price' => $sellingPrice,
            'original_selling_price' => $this->formatAmount($rawSellingPrice),
            'original_selling_currency' => $sellingCurrency,
            'shipping_cost' => $shippingCost,
            'estimated_fees' => $estimatedFees,
            'other_costs' => $otherCosts,
            'total_cost' => $totalCost,
            'estimated_profit' => $estimatedProfit,
            'profit_margin' => $profitMargin,
            'return_on_investment' => $roi,
            'opportunity_score' => number_format($opportunityScore, 2, '.', ''),
            'risk_score' => number_format($riskScore, 2, '.', ''),
            'competition_level' => $competitionLevel,
            'is_profitable' => bccomp($estimatedProfit, '0.0000', 4) > 0,
            'calculated_at' => now()->toISOString(),
        ];
    }

    /**
     * Calculate metrics and store a ProductMetric snapshot attached to a ProductListing or CanonicalProduct.
     *
     * @param ProductListing|CanonicalProduct $product
     * @param array<string, mixed> $customParams
     * @return ProductMetric
     */
    public function calculateAndPersist(mixed $product, array $customParams = []): ProductMetric
    {
        $listingId = null;
        $canonicalId = null;

        if ($product instanceof ProductListing) {
            $listingId = $product->id;
            $canonicalId = $product->canonical_product_id;
            $observedPrice = $product->price;
            $currency = $product->currency;
            $rating = $product->rating;
            $reviewsCount = $product->reviews_count;
        } elseif ($product instanceof CanonicalProduct) {
            $canonicalId = $product->id;
            $cheapest = $product->activeListings()->orderBy('price')->first();
            $listingId = $cheapest?->id;
            $observedPrice = $cheapest?->price ?? 0.0;
            $currency = $product->base_currency ?: ($cheapest?->currency ?? 'USD');
            $rating = $cheapest?->rating;
            $reviewsCount = $cheapest?->reviews_count ?? 0;
        } else {
            throw new InvalidArgumentException('Product must be an instance of ProductListing or CanonicalProduct.');
        }

        // Default purchase price to observed price if not specified
        $purchasePrice = $customParams['purchase_price'] ?? $observedPrice;
        $sellingPrice = $customParams['selling_price']
            ?? (isset($product->original_price) && $product->original_price ? $product->original_price : bcmul((string) $purchasePrice, '1.50', 4));

        $params = array_merge([
            'currency' => $currency,
            'purchase_price' => $purchasePrice,
            'purchase_currency' => $customParams['purchase_currency'] ?? $currency,
            'selling_price' => $sellingPrice,
            'selling_currency' => $customParams['selling_currency'] ?? $currency,
            'shipping_cost' => $customParams['shipping_cost'] ?? 0.0,
            'estimated_fees' => $customParams['estimated_fees'] ?? 0.0,
            'other_costs' => $customParams['other_costs'] ?? 0.0,
            'rating' => $rating,
            'reviews_count' => $reviewsCount,
        ], $customParams);

        $results = $this->calculate($params);

        return ProductMetric::create([
            'canonical_product_id' => $canonicalId,
            'product_id' => $listingId,
            'purchase_price' => $results['purchase_price'],
            'selling_price' => $results['selling_price'],
            'shipping_cost' => $results['shipping_cost'],
            'estimated_fees' => $results['estimated_fees'],
            'other_costs' => $results['other_costs'],
            'estimated_profit' => $results['estimated_profit'],
            'profit_margin' => $results['profit_margin'],
            'return_on_investment' => $results['return_on_investment'],
            'opportunity_score' => $results['opportunity_score'],
            'risk_score' => $results['risk_score'],
            'competition_level' => $results['competition_level'],
            'calculation_notes' => [
                'is_profitable' => $results['is_profitable'],
                'currency' => $results['currency'],
                'original_purchase_currency' => $results['original_purchase_currency'],
                'original_selling_currency' => $results['original_selling_currency'],
            ],
            'calculated_at' => now(),
        ]);
    }

    /**
     * Compute Risk Score (0 - 100).
     */
    protected function computeRiskScore(float $margin, ?float $rating, int $reviewsCount, float $purchaseCost): float
    {
        $risk = 0.0;

        // Low or negative margin penalty (up to 40 pts)
        if ($margin <= 0) {
            $risk += 40.0;
        } elseif ($margin < 15.0) {
            $risk += 25.0;
        } elseif ($margin < 25.0) {
            $risk += 12.0;
        }

        // Low product quality / rating risk (up to 30 pts)
        if ($rating !== null) {
            if ($rating < 3.5) {
                $risk += 30.0;
            } elseif ($rating < 4.0) {
                $risk += 18.0;
            } elseif ($rating < 4.3) {
                $risk += 8.0;
            }
        } else {
            $risk += 15.0; // Uncertainty penalty
        }

        // Low proof of demand / review volume (up to 20 pts)
        if ($reviewsCount < 10) {
            $risk += 20.0;
        } elseif ($reviewsCount < 50) {
            $risk += 10.0;
        }

        // High capital requirement risk (up to 10 pts)
        if ($purchaseCost > 100.0) {
            $risk += 10.0;
        } elseif ($purchaseCost > 50.0) {
            $risk += 5.0;
        }

        return max(0.0, min(100.0, round($risk, 2)));
    }

    /**
     * Derive competition level string.
     */
    protected function computeCompetitionLevel(int $reviewsCount): string
    {
        if ($reviewsCount >= 1000) {
            return 'high';
        }

        if ($reviewsCount >= 100) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Format any amount to a string representation suitable for BCMath.
     */
    protected function formatAmount(mixed $amount): string
    {
        $val = (float) $amount;
        return number_format($val, 4, '.', '');
    }
}
