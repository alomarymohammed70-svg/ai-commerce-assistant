<?php

namespace App\Services\Matching;

use App\Contracts\Matching\ProductMatchingServiceInterface;
use App\DTOs\Matching\ProductMatchResult;
use App\Models\CanonicalProduct;
use App\Models\ProductListing;

class ProductMatchingService implements ProductMatchingServiceInterface
{
    /**
     * Stop words excluded during title tokenization.
     */
    protected const STOP_WORDS = [
        'with', 'for', 'the', 'and', 'a', 'an', 'in', 'on', 'to', 'of',
        'at', 'by', 'from', 'new', 'original', 'hot', 'best', 'sale',
    ];

    public function matchListing(ProductListing $listing): ProductMatchResult
    {
        // Try to extract potential model number from title if not explicitly set
        $modelNumber = $this->extractModelNumber($listing->title);

        return $this->matchAttributes(
            title: $listing->title,
            brand: $listing->brand,
            modelNumber: $modelNumber,
            categoryId: $listing->category_id
        );
    }

    public function matchAttributes(
        string $title,
        ?string $brand = null,
        ?string $modelNumber = null,
        ?int $categoryId = null
    ): ProductMatchResult {
        $normalizedTitle = $this->normalizeString($title);
        $normalizedBrand = $brand ? $this->normalizeString($brand) : null;
        $normalizedModel = $modelNumber ? strtoupper(trim($modelNumber)) : null;

        // Query potential candidate canonical products
        $candidatesQuery = CanonicalProduct::query();

        if ($normalizedBrand) {
            $candidatesQuery->where(function ($q) use ($normalizedBrand) {
                $q->whereRaw('LOWER(brand) = ?', [$normalizedBrand])
                    ->orWhereNull('brand');
            });
        }

        if ($categoryId) {
            $candidatesQuery->orWhere('category_id', $categoryId);
        }

        $candidates = $candidatesQuery->limit(50)->get();

        if ($candidates->isEmpty()) {
            // Fallback: check top canonical products by title keyword search
            $tokens = $this->tokenize($title);
            if (! empty($tokens)) {
                $firstToken = $tokens[0];
                $candidates = CanonicalProduct::where('name', 'LIKE', '%' . $firstToken . '%')
                    ->limit(20)
                    ->get();
            }
        }

        if ($candidates->isEmpty()) {
            return new ProductMatchResult(
                status: ProductMatchResult::NO_MATCH,
                confidenceScore: 0.0,
                matchReasons: ['No candidate canonical products found in database.']
            );
        }

        $bestCandidate = null;
        $bestScore = 0.0;
        $bestReasons = [];

        foreach ($candidates as $candidate) {
            [$score, $reasons] = $this->evaluateCandidate(
                $candidate,
                $title,
                $normalizedBrand,
                $normalizedModel,
                $categoryId
            );

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCandidate = $candidate;
                $bestReasons = $reasons;
            }
        }

        $bestScore = round(max(0.0, min(1.0, $bestScore)), 2);

        if ($bestScore >= 0.80) {
            $status = ProductMatchResult::MATCH;
        } elseif ($bestScore >= 0.50) {
            $status = ProductMatchResult::POSSIBLE_MATCH;
        } else {
            $status = ProductMatchResult::NO_MATCH;
        }

        return new ProductMatchResult(
            status: $status,
            canonicalProduct: $bestScore >= 0.50 ? $bestCandidate : null,
            confidenceScore: $bestScore,
            matchReasons: $bestReasons
        );
    }

    /**
     * Evaluate matching confidence between a listing and a canonical candidate.
     *
     * @return array{0: float, 1: array<string>}
     */
    protected function evaluateCandidate(
        CanonicalProduct $candidate,
        string $listingTitle,
        ?string $listingBrand,
        ?string $listingModel,
        ?int $listingCategoryId
    ): array {
        $score = 0.0;
        $reasons = [];

        $candidateBrand = $candidate->brand ? $this->normalizeString($candidate->brand) : null;
        $candidateModel = $candidate->model_number ? strtoupper(trim($candidate->model_number)) : null;

        // Try extracting candidate model from its name if model_number column was not populated
        if (! $candidateModel) {
            $candidateModel = $this->extractModelNumber($candidate->name);
        }

        if (! $listingModel) {
            $listingModel = $this->extractModelNumber($listingTitle);
        }

        // 1. Model Number Matching (high weight)
        if ($listingModel && $candidateModel) {
            if ($listingModel === $candidateModel) {
                $score += 0.50;
                $reasons[] = "Exact model number match [{$candidateModel}]";
            }
        } elseif ($candidateModel && str_contains(strtoupper($listingTitle), $candidateModel)) {
            $score += 0.45;
            $reasons[] = "Listing title contains candidate model [{$candidateModel}]";
        }

        // 2. Brand Matching
        if ($listingBrand && $candidateBrand) {
            if ($listingBrand === $candidateBrand) {
                $score += 0.35;
                $reasons[] = "Exact brand match [{$candidate->brand}]";
            } else {
                // Conflicting brand penalty
                $score -= 0.35;
                $reasons[] = "Conflicting brand: candidate [{$candidate->brand}] vs listing [{$listingBrand}]";
            }
        } elseif (! $listingBrand && $candidateBrand) {
            if (str_contains($this->normalizeString($listingTitle), $candidateBrand)) {
                $score += 0.25;
                $reasons[] = "Brand [{$candidate->brand}] found within listing title";
            }
        }

        // 3. Category Match
        if ($listingCategoryId && $candidate->category_id === $listingCategoryId) {
            $score += 0.10;
            $reasons[] = 'Matching category';
        }

        // 4. Title Token Overlap (Jaccard Similarity)
        $listingTokens = $this->tokenize($listingTitle);
        $candidateTokens = $this->tokenize($candidate->name);

        $jaccard = $this->computeJaccardSimilarity($listingTokens, $candidateTokens);
        if ($jaccard > 0.05) {
            $tokenContribution = $jaccard * 0.50;
            $score += $tokenContribution;
            $reasons[] = sprintf('Title token similarity: %.2f%%', $jaccard * 100);

            if ($jaccard >= 0.50) {
                $score += 0.10; // High token convergence bonus
            }
        }

        // 5. Direct substring containment
        $normCandidate = $this->normalizeString($candidate->name);
        $normListing = $this->normalizeString($listingTitle);
        if (str_contains($normListing, $normCandidate)) {
            $score += 0.15;
            $reasons[] = 'Full canonical name contained in listing title';
        }

        return [$score, $reasons];
    }

    /**
     * Compute Jaccard Similarity index between two token arrays.
     *
     * @param array<string> $tokensA
     * @param array<string> $tokensB
     * @return float
     */
    protected function computeJaccardSimilarity(array $tokensA, array $tokensB): float
    {
        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }

        $intersection = array_intersect($tokensA, $tokensB);
        $union = array_unique(array_merge($tokensA, $tokensB));

        return count($union) > 0 ? count($intersection) / count($union) : 0.0;
    }

    /**
     * Tokenize text into normalized unique words, removing stop words and punctuation.
     *
     * @param string $text
     * @return array<string>
     */
    protected function tokenize(string $text): array
    {
        $normalized = $this->normalizeString($text);
        $words = preg_split('/[\s\-_,\.\/]+/', $normalized) ?: [];

        $filtered = array_filter($words, function ($w) {
            $len = strlen($w);
            return $len > 1 && ! in_array($w, self::STOP_WORDS, true);
        });

        return array_values(array_unique($filtered));
    }

    /**
     * Normalize string: lowercase, strip special characters.
     */
    protected function normalizeString(string $value): string
    {
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', mb_strtolower($value));
        return trim(preg_replace('/\s+/', ' ', (string) $cleaned));
    }

    /**
     * Heuristic extractor for model numbers (e.g. "WH-1000XM4", "A1289", "WF-C500").
     */
    public function extractModelNumber(string $title): ?string
    {
        if (preg_match('/\b([A-Z]{1,4}[-_]?[0-9]{2,5}[A-Z0-9]{0,4})\b/i', $title, $matches)) {
            return strtoupper(str_replace('_', '-', $matches[1]));
        }

        return null;
    }
}
