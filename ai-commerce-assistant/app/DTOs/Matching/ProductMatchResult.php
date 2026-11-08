<?php

namespace App\DTOs\Matching;

use App\Models\CanonicalProduct;

class ProductMatchResult
{
    public const MATCH = 'MATCH';
    public const POSSIBLE_MATCH = 'POSSIBLE_MATCH';
    public const NO_MATCH = 'NO_MATCH';

    public function __construct(
        public readonly string $status,
        public readonly ?CanonicalProduct $canonicalProduct = null,
        public readonly float $confidenceScore = 0.0,
        public readonly array $matchReasons = []
    ) {}

    public function isMatch(): bool
    {
        return $this->status === self::MATCH;
    }

    public function isPossibleMatch(): bool
    {
        return $this->status === self::POSSIBLE_MATCH;
    }

    public function isNoMatch(): bool
    {
        return $this->status === self::NO_MATCH;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'canonical_product_id' => $this->canonicalProduct?->id,
            'confidence_score' => $this->confidenceScore,
            'match_reasons' => $this->matchReasons,
        ];
    }
}

