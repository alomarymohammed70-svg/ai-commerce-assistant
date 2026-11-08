<?php

namespace App\Services\AI\Prompts\Contracts;

interface PromptInterface
{
    /**
     * Unique identifier version string (e.g. 'v1', 'v2').
     */
    public function getVersion(): string;

    /**
     * The system instructions defining role, boundaries, rules, and data truth.
     */
    public function getSystemPrompt(): string;

    /**
     * Build the user message combining demarcated evidence and context.
     *
     * @param array<string, mixed> $context
     */
    public function formatUserMessage(array $context): string;

    /**
     * Return JSON schema specification for structured output.
     *
     * @return array<string, mixed>
     */
    public function getExpectedJsonSchema(): array;
}

