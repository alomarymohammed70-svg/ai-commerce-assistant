<?php

namespace App\Contracts\AI;

use App\DTOs\AI\AIProviderResultDto;
use App\Exceptions\AI\AIException;

interface AIProviderInterface
{
    /**
     * Perform structured commercial intelligence analysis on the provided context.
     *
     * @param array<string, mixed> $context
     * @param string|null $promptVersion
     * @return AIProviderResultDto
     *
     * @throws AIException
     */
    public function analyze(array $context, ?string $promptVersion = null): AIProviderResultDto;

    /**
     * Unique provider identifier (e.g. 'openai', 'mock').
     */
    public function getProviderName(): string;

    /**
     * Active AI model name (e.g. 'gpt-4o-mini').
     */
    public function getModelName(): string;

    /**
     * Determine if necessary credentials and configuration exist.
     */
    public function isConfigured(): bool;
}

