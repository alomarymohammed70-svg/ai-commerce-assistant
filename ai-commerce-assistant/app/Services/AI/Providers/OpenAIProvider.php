<?php

namespace App\Services\AI\Providers;

use App\Contracts\AI\AIProviderInterface;
use App\DTOs\AI\AIProviderResultDto;
use App\Exceptions\AI\AIAuthenticationException;
use App\Exceptions\AI\AIProviderException;
use App\Exceptions\AI\AIRateLimitException;
use App\Exceptions\AI\AISchemaValidationException;
use App\Services\AI\Prompts\Contracts\PromptInterface;
use App\Services\AI\Prompts\ProductAnalysisPromptV1;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider implements AIProviderInterface
{
    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $model = null,
        protected ?string $baseUrl = null,
        protected ?float $temperature = null,
        protected ?int $timeout = null,
        protected ?int $maxRetries = null
    ) {
        $this->apiKey = $apiKey ?? (string) config('ai.providers.openai.api_key');
        $this->model = $model ?? (string) config('ai.providers.openai.model', 'gpt-4o-mini');
        $this->baseUrl = rtrim($baseUrl ?? (string) config('ai.providers.openai.base_url', 'https://api.openai.com/v1'), '/');
        $this->temperature = $temperature ?? (float) config('ai.providers.openai.temperature', 0.1);
        $this->timeout = $timeout ?? (int) config('ai.timeout', 30);
        $this->maxRetries = $maxRetries ?? (int) config('ai.max_retries', 2);
    }

    public function getProviderName(): string
    {
        return 'openai';
    }

    public function getModelName(): string
    {
        return $this->model;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Resolve the active prompt instance according to version.
     */
    protected function resolvePrompt(?string $promptVersion = null): PromptInterface
    {
        // Version router: currently v1
        return new ProductAnalysisPromptV1();
    }

    /**
     * Call OpenAI Chat Completions API with structured JSON response format.
     */
    public function analyze(array $context, ?string $promptVersion = null): AIProviderResultDto
    {
        if (! $this->isConfigured()) {
            throw new AIAuthenticationException('OpenAI API key is missing or not configured in environment.');
        }

        $prompt = $this->resolvePrompt($promptVersion);
        $systemMessage = $prompt->getSystemPrompt();
        $userMessage = $prompt->formatUserMessage($context);

        $endpoint = $this->baseUrl . '/chat/completions';
        $startTime = microtime(true);

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->maxRetries, 500, function ($exception) {
                    return $exception instanceof ConnectionException;
                })
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'model' => $this->model,
                    'temperature' => $this->temperature,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemMessage],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                ]);
        } catch (ConnectionException $e) {
            Log::error('OpenAI Connection Failure: ' . $e->getMessage(), [
                'provider' => $this->getProviderName(),
                'model' => $this->getModelName(),
            ]);

            throw new AIProviderException('Network connection to OpenAI service timed out or was unreachable.', 504, null, $e);
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        // Check for HTTP errors
        if ($response->failed()) {
            $status = $response->status();
            $body = $response->json() ?? [];
            $errorMessage = $body['error']['message'] ?? $response->body();

            if ($status === 401) {
                throw new AIAuthenticationException('OpenAI authentication failed: Invalid API credentials.', 401, [
                    'provider' => $this->getProviderName(),
                ]);
            }

            if ($status === 429) {
                $retryAfter = (int) ($response->header('Retry-After') ?? 10);
                throw new AIRateLimitException("OpenAI rate limit exceeded: {$errorMessage}", 429, $retryAfter);
            }

            if ($status >= 500) {
                throw new AIProviderException("OpenAI upstream error [{$status}]: {$errorMessage}", 503, [
                    'status' => $status,
                ]);
            }

            throw new AIProviderException("OpenAI request rejected [{$status}]: {$errorMessage}", $status);
        }

        $responseData = $response->json();
        $content = $responseData['choices'][0]['message']['content'] ?? null;

        if (empty($content)) {
            throw new AISchemaValidationException('OpenAI returned an empty completion content.', 422);
        }

        $structured = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($structured)) {
            throw new AISchemaValidationException('OpenAI response content is not valid JSON: ' . json_last_error_msg(), 422, [
                'raw_content' => substr($content, 0, 500),
            ]);
        }

        $tokensUsed = (int) ($responseData['usage']['total_tokens'] ?? 0);

        return new AIProviderResultDto(
            provider: $this->getProviderName(),
            model: $this->getModelName(),
            rawResponse: $responseData,
            structuredAnalysis: $structured,
            durationMs: $durationMs,
            tokensUsed: $tokensUsed
        );
    }
}

