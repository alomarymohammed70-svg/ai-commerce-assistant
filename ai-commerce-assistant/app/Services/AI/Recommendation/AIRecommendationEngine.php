<?php

namespace App\Services\AI\Recommendation;

use App\DTOs\AI\AIProviderResultDto;
use App\Exceptions\AI\AIException;
use App\Models\AIAnalysis;
use App\Models\CanonicalProduct;
use App\Models\ProductListing;
use App\Services\AI\AIProviderManager;
use App\Services\AI\Context\AIContextBuilder;
use App\Services\AI\Validation\AIResponseValidator;
use Illuminate\Support\Facades\Log;

class AIRecommendationEngine
{
    public function __construct(
        protected AIContextBuilder $contextBuilder,
        protected AIProviderManager $providerManager,
        protected AIResponseValidator $validator
    ) {}

    /**
     * Run full AI commerce intelligence pipeline for a Canonical Product or Product Listing.
     *
     * @param CanonicalProduct|ProductListing $subject
     * @param array<string, mixed> $financialInputs
     * @param int|null $userId
     * @param string|null $driverOverride
     * @param bool $shouldPersist
     * @return array<string, mixed>
     *
     * @throws AIException
     */
    public function analyze(
        mixed $subject,
        array $financialInputs = [],
        ?int $userId = null,
        ?string $driverOverride = null,
        bool $shouldPersist = true
    ): array {
        $startTime = microtime(true);
        $promptVersion = (string) config('ai.prompt_version', 'v1');

        // 1. Build Demarcated Context with Data Truth Classification
        $context = $this->contextBuilder->build($subject, $financialInputs);

        // 2. Resolve AI Provider
        $provider = $this->providerManager->resolve($driverOverride);

        // 3. Execute Provider Call
        $providerResult = $provider->analyze($context, $promptVersion);

        // 4. Validate and Reconcile AI Output against Laravel Deterministic Truth
        $validatedAnalysis = $this->validator->validate($providerResult->structuredAnalysis, $context);

        $totalDurationMs = round((microtime(true) - $startTime) * 1000, 2);

        $canonicalId = $subject instanceof CanonicalProduct ? $subject->id : $subject->canonical_product_id;
        $listingId = $subject instanceof ProductListing ? $subject->id : null;

        $record = null;
        if ($shouldPersist) {
            $record = AIAnalysis::create([
                'canonical_product_id' => $canonicalId,
                'product_listing_id' => $listingId,
                'user_id' => $userId,
                'provider' => $provider->getProviderName(),
                'model' => $provider->getModelName(),
                'prompt_version' => $promptVersion,
                'decision' => $validatedAnalysis['recommendation']['decision'],
                'opportunity_score' => $validatedAnalysis['opportunity']['score'],
                'risk_score' => $validatedAnalysis['risk']['score'],
                'confidence' => $validatedAnalysis['recommendation']['confidence'],
                'deterministic_metrics' => $context['financial_analysis'],
                'context_snapshot' => $context,
                'ai_response' => $validatedAnalysis,
                'duration_ms' => $totalDurationMs,
                'tokens_used' => $providerResult->tokensUsed,
            ]);
        }

        Log::info('AI Commerce Analysis Executed Successfully', [
            'subject_id' => $canonicalId ?: $listingId,
            'provider' => $provider->getProviderName(),
            'model' => $provider->getModelName(),
            'decision' => $validatedAnalysis['recommendation']['decision'],
            'duration_ms' => $totalDurationMs,
        ]);

        return [
            'id' => $record?->id,
            'canonical_product_id' => $canonicalId,
            'product_listing_id' => $listingId,
            'provider' => $provider->getProviderName(),
            'model' => $provider->getModelName(),
            'prompt_version' => $promptVersion,
            'decision' => $validatedAnalysis['recommendation']['decision'],
            'confidence' => $validatedAnalysis['recommendation']['confidence'],
            'opportunity_score' => $validatedAnalysis['opportunity']['score'],
            'risk_score' => $validatedAnalysis['risk']['score'],
            'analysis' => $validatedAnalysis,
            'deterministic_metrics' => $context['financial_analysis'],
            'duration_ms' => $totalDurationMs,
            'tokens_used' => $providerResult->tokensUsed,
            'created_at' => $record ? $record->created_at->toISOString() : now()->toISOString(),
        ];
    }
}

