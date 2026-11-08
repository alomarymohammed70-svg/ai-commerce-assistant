<?php

namespace App\Contracts\Analysis;

interface OpportunityScoreStrategyInterface
{
    /**
     * Unique identifier or human-readable name of the scoring strategy.
     */
    public function getName(): string;

    /**
     * Compute deterministic or heuristic opportunity score (0.0 to 100.0).
     *
     * @param float $margin Profit margin percentage
     * @param float|null $rating Product rating (e.g. 1.0 to 5.0)
     * @param int $reviewsCount Total volume of customer reviews
     * @param float $purchaseCost Cost of acquiring the product
     * @param array<string, mixed> $context Additional contextual parameters (category, rank, etc.)
     * @return float Score between 0.00 and 100.00
     */
    public function compute(
        float $margin,
        ?float $rating,
        int $reviewsCount,
        float $purchaseCost,
        array $context = []
    ): float;
}

