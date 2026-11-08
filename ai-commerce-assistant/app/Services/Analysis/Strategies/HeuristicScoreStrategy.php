<?php

namespace App\Services\Analysis\Strategies;

use App\Contracts\Analysis\OpportunityScoreStrategyInterface;

/**
 * Deterministic MVP heuristic strategy for Opportunity Scoring.
 * Easily replaceable or augmentable by AI/ML scoring strategies in future phases.
 */
class HeuristicScoreStrategy implements OpportunityScoreStrategyInterface
{
    public function getName(): string
    {
        return 'heuristic_mvp';
    }

    public function compute(
        float $margin,
        ?float $rating,
        int $reviewsCount,
        float $purchaseCost,
        array $context = []
    ): float {
        $score = 0.0;

        // 1. Margin component (up to 40 pts)
        if ($margin > 0) {
            $score += min(40.0, $margin * 0.8);
        }

        // 2. Rating component (up to 30 pts)
        if ($rating !== null) {
            $score += min(30.0, ($rating / 5.0) * 30.0);
        } else {
            $score += 15.0; // Default median for missing rating
        }

        // 3. Market traction / Review volume (up to 20 pts)
        if ($reviewsCount >= 500) {
            $score += 20.0;
        } elseif ($reviewsCount >= 100) {
            $score += 15.0;
        } elseif ($reviewsCount >= 20) {
            $score += 10.0;
        } else {
            $score += 4.0;
        }

        // 4. Affordability / Low barrier of entry (up to 10 pts)
        if ($purchaseCost > 0 && $purchaseCost <= 25.0) {
            $score += 10.0;
        } elseif ($purchaseCost <= 60.0) {
            $score += 6.0;
        } else {
            $score += 2.0;
        }

        // 5. Negative margin penalty (a losing product is a poor opportunity)
        if ($margin <= 0) {
            $score = max(0.0, $score - 15.0);
        }

        return max(0.0, min(100.0, round($score, 2)));
    }
}

