<?php

namespace App\Services\AI;

use App\Contracts\AI\AIProviderInterface;
use App\Exceptions\AI\AIProviderException;
use App\Services\AI\Providers\MockAIProvider;
use App\Services\AI\Providers\OpenAIProvider;
use Illuminate\Contracts\Foundation\Application;

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
     *
     * @param string|null $driverOverride
     * @return AIProviderInterface
     *
     * @throws AIProviderException
     */
    public function resolve(?string $driverOverride = null): AIProviderInterface
    {
        if ($this->customProvider !== null && $driverOverride === null) {
            return $this->customProvider;
        }

        $driver = strtolower(trim((string) ($driverOverride ?: config('ai.default', 'mock'))));

        return match ($driver) {
            'mock' => new MockAIProvider(
                model: (string) config('ai.providers.mock.model', 'mock-commerce-intelligence-v1')
            ),
            'openai' => new OpenAIProvider(),
            default => throw new AIProviderException("Unsupported AI provider driver [{$driver}]. Supported: mock, openai.", 500),
        };
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

