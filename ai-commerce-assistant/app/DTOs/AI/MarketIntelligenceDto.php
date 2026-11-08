<?php

namespace App\DTOs\AI;

/**
 * Data Transfer Object representing AI-driven market intelligence
 * and viability assessment for a specific product or opportunity.
 */
class MarketIntelligenceDto
{
    public function __construct(
        public readonly string $productId,
        public readonly float $demandScore,         // 0 - 100
        public readonly float $competitionScore,    // 0 - 100 (lower means less competitive barrier)
        public readonly float $estimatedProfitMargin, // Percentage (e.g. 35.5%)
        public readonly string $riskLevel,          // 'low', 'medium', 'high'
        public readonly string $aiRecommendation,   // AI-generated reasoning
        public readonly array $suggestedSellingChannels = [],
        public readonly array $marketMetrics = []
    ) {}

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'demand_score' => $this->demandScore,
            'competition_score' => $this->competitionScore,
            'estimated_profit_margin' => $this->estimatedProfitMargin,
            'risk_level' => $this->riskLevel,
            'ai_recommendation' => $this->aiRecommendation,
            'suggested_selling_channels' => $this->suggestedSellingChannels,
            'market_metrics' => $this->marketMetrics,
        ];
    }
}

