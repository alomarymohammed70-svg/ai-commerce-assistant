<?php

namespace App\Contracts\AI;

use App\DTOs\AI\MarketIntelligenceDto;
use App\DTOs\ExternalSources\ExternalProductDto;

/**
 * Contract defining standard operations for the AI Analysis and Market Intelligence engine.
 */
interface MarketAnalysisEngineInterface
{
    /**
     * Analyze a single product against target market criteria.
     *
     * @param ExternalProductDto $product
     * @param array<string, mixed> $context
     * @return MarketIntelligenceDto
     */
    public function analyzeProduct(ExternalProductDto $product, array $context = []): MarketIntelligenceDto;

    /**
     * Evaluate multiple product candidates against user input constraints
     * (e.g. "$500 budget, high demand, low shipping weight").
     *
     * @param float $budget
     * @param array<int, ExternalProductDto> $candidates
     * @param array<string, mixed> $preferences
     * @return array<int, MarketIntelligenceDto>
     */
    public function rankOpportunities(float $budget, array $candidates, array $preferences = []): array;
}

