<?php

namespace Tests\Unit\AI;

use App\Contracts\AI\AIProviderInterface;
use App\Exceptions\AI\AIAuthenticationException;
use App\Exceptions\AI\AIProviderException;
use App\Services\AI\AIProviderManager;
use App\Services\AI\Providers\MockAIProvider;
use App\Services\AI\Providers\OpenAIProvider;
use Tests\TestCase;

class AIProviderManagerTest extends TestCase
{
    protected AIProviderManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new AIProviderManager($this->app);
    }

    public function test_resolves_mock_provider_by_default_or_explicit_driver(): void
    {
        config(['ai.default' => 'mock']);

        $provider = $this->manager->resolve();

        $this->assertInstanceOf(AIProviderInterface::class, $provider);
        $this->assertInstanceOf(MockAIProvider::class, $provider);
        $this->assertEquals('mock', $provider->getProviderName());
        $this->assertTrue($provider->isConfigured());
    }

    public function test_resolves_openai_provider_when_specified(): void
    {
        $provider = $this->manager->resolve('openai');

        $this->assertInstanceOf(AIProviderInterface::class, $provider);
        $this->assertInstanceOf(OpenAIProvider::class, $provider);
        $this->assertEquals('openai', $provider->getProviderName());
    }

    public function test_throws_exception_for_unsupported_provider(): void
    {
        $this->expectException(AIProviderException::class);
        $this->expectExceptionMessage('Unsupported AI provider driver [unsupported_ai]');

        $this->manager->resolve('unsupported_ai');
    }

    public function test_custom_provider_injection_works(): void
    {
        $mock = new MockAIProvider(model: 'custom-injected-model');
        $this->manager->setCustomProvider($mock);

        $resolved = $this->manager->resolve();

        $this->assertSame($mock, $resolved);
        $this->assertEquals('custom-injected-model', $resolved->getModelName());
    }

    public function test_blocks_mock_provider_and_driver_override_in_production_environment(): void
    {
        config([
            'ai.default' => 'mock',
            'ai.providers.openai.api_key' => 'sk-prod-test-key',
        ]);
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(AIProviderException::class);
        $this->expectExceptionCode(503);
        $this->expectExceptionMessage('Mock AI provider is not permitted in production');

        $this->manager->resolve('openai');
    }

    public function test_production_unconfigured_provider_fails_closed_instead_of_mock_fallback(): void
    {
        config([
            'ai.default' => null,
            'ai.providers.openai.api_key' => '',
        ]);
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(AIAuthenticationException::class);
        $this->expectExceptionCode(503);
        $this->expectExceptionMessage('missing required production credentials');

        $this->manager->resolve();
    }

    public function test_production_resolves_configured_openai_provider_and_blocks_mock_override(): void
    {
        config([
            'ai.default' => 'openai',
            'ai.providers.openai.api_key' => 'sk-prod-valid-key-123',
        ]);
        $this->app->detectEnvironment(fn () => 'production');

        $provider = $this->manager->resolve('mock');

        $this->assertInstanceOf(OpenAIProvider::class, $provider);
        $this->assertEquals('openai', $provider->getProviderName());
        $this->assertTrue($provider->isConfigured());
    }

    public function test_blocks_injected_custom_mock_provider_in_production_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->manager->setCustomProvider(new MockAIProvider(model: 'custom-mock'));

        $this->expectException(AIProviderException::class);
        $this->expectExceptionCode(503);
        $this->expectExceptionMessage('MockAIProvider is not permitted in production');

        $this->manager->resolve();
    }
}

