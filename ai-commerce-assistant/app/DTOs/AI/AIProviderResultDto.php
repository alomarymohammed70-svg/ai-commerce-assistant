<?php

namespace App\DTOs\AI;

class AIProviderResultDto
{
    /**
     * @param string $provider Identifier of the provider (e.g. 'openai', 'mock')
     * @param string $model Model name used (e.g. 'gpt-4o-mini')
     * @param array<string, mixed> $rawResponse Full decoded response array from provider
     * @param array<string, mixed> $structuredAnalysis Parsed and validated analysis payload
     * @param float $durationMs Elapsed time in milliseconds
     * @param int|null $tokensUsed Total tokens consumed if reported by provider
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly array $rawResponse,
        public readonly array $structuredAnalysis,
        public readonly float $durationMs,
        public readonly ?int $tokensUsed = null
    ) {}
}

