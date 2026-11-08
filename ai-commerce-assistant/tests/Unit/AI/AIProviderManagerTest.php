<?php

namespace Tests\Unit\AI;

use App\Contracts\AI\AIProviderInterface;
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
}

