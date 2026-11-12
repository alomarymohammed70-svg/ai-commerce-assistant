<?php

namespace App\Services\AI;

use App\Contracts\AI\AIProviderInterface;
use App\Exceptions\AI\AIAuthenticationException;
use App\Exceptions\AI\AIProviderException;
use App\Services\AI\Providers\MockAIProvider;
use App\Services\AI\Providers\OpenAIProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;

class AIProviderManager
{
    /**
     * Optional custom provider instance injected (useful for testing).
     */
    protected ?AIProviderInterface $customProvider = null;

    public function __construct(
        protected Application $app
    ) {}

    /**
     * Resolve the AI provider to use.
     * In production, server configuration (ai.default) is strictly authoritative,
     * mock provider is forbidden, and missing live provider credentials fail closed.
     *
     * @param string|null $driverOverride
     * @return AIProviderInterface
     *
     * @throws AIProviderException
     * @throws AIAuthenticationException
     */
    public function resolve(?string $driverOverride = null): AIProviderInterface
    {
        $isProduction = $this->app->environment('production');

        if ($isProduction) {
            if ($driverOverride !== null && trim($driverOverride) !== '') {
                Log::warning('ai.service_driver_override_blocked_in_production', [
                    'requested_driver' => $driverOverride,
                    'configured_default' => config('ai.default', 'openai'),
                ]);
            }
            $driverOverride = null;
        }

        if ($this->customProvider !== null && ($driverOverride === null || trim($driverOverride) === '')) {
            if ($isProduction && $this->customProvider instanceof MockAIProvider) {
                Log::error('ai.mock_provider_blocked_in_production', [
                    'source' => 'custom_provider',
                ]);

                throw new AIProviderException(
                    'MockAIProvider is not permitted in production.',
                    503
                );
            }

            if ($isProduction && ! $this->customProvider->isConfigured()) {
                Log::error('ai.provider_not_configured_in_production', [
                    'provider' => $this->customProvider->getProviderName(),
                ]);

                throw new AIAuthenticationException(
                    "AI provider [{$this->customProvider->getProviderName()}] is missing required production credentials.",
                    503
                );
            }

            return $this->customProvider;
        }

        $fallbackDefault = $isProduction ? 'openai' : 'mock';
        $configuredDefault = trim((string) config('ai.default', ''));
        $driver = strtolower(trim((string) ($driverOverride ?: ($configuredDefault !== '' ? $configuredDefault : $fallbackDefault))));

        if ($isProduction && $driver === 'mock') {
            Log::error('ai.mock_provider_blocked_in_production', [
                'configured_default' => $configuredDefault !== '' ? $configuredDefault : null,
            ]);

            throw new AIProviderException(
                'Mock AI provider is not permitted in production. Configure a live AI_PROVIDER (e.g. openai) with valid credentials.',
                503
            );
        }

        $provider = match ($driver) {
            'mock' => new MockAIProvider(
                model: (string) config('ai.providers.mock.model', 'mock-commerce-intelligence-v1')
            ),
            'openai' => new OpenAIProvider(),
            default => throw new AIProviderException("Unsupported AI provider driver [{$driver}]. Supported: mock, openai.", 500),
        };

        if ($isProduction && ! $provider->isConfigured()) {
            Log::error('ai.provider_not_configured_in_production', [
                'provider' => $provider->getProviderName(),
            ]);

            throw new AIAuthenticationException(
                "AI provider [{$provider->getProviderName()}] is missing required production credentials (AI_API_KEY).",
                503
            );
        }

        return $provider;
    }

    /**
     * Set a custom AI provider (for testing and mocking).
     */
    public function setCustomProvider(?AIProviderInterface $provider): self
    {
        $this->customProvider = $provider;
        return $this;
    }
}

