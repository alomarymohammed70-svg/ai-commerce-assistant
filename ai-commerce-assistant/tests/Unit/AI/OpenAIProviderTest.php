<?php

namespace Tests\Unit\AI;

use App\Exceptions\AI\AIAuthenticationException;
use App\Exceptions\AI\AIProviderException;
use App\Exceptions\AI\AIRateLimitException;
use App\Exceptions\AI\AISchemaValidationException;
use App\Services\AI\Providers\OpenAIProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAIProviderTest extends TestCase
{
    public function test_is_configured_checks_api_key(): void
    {
        $unconfigured = new OpenAIProvider(apiKey: '');
        $this->assertFalse($unconfigured->isConfigured());

        $configured = new OpenAIProvider(apiKey: 'sk-test-valid-key-12345');
        $this->assertTrue($configured->isConfigured());
    }

    public function test_throws_authentication_exception_when_not_configured(): void
    {
        $provider = new OpenAIProvider(apiKey: '');

        $this->expectException(AIAuthenticationException::class);
        $this->expectExceptionMessage('OpenAI API key is missing or not configured');

        $provider->analyze(['test' => true]);
    }

    public function test_successful_structured_completion_call(): void
    {
        $sampleStructured = [
            'recommendation' => [
                'decision' => 'BUY',
                'confidence' => 0.88,
                'summary' => 'Viable margin product.',
            ],
            'opportunity' => ['score' => 80, 'level' => 'HIGH', 'justification' => 'OK'],
            'profitability' => ['profit' => '50.00', 'margin' => '30%', 'assessment' => 'Healthy'],
            'competition' => ['level' => 'LOW', 'score' => 20, 'summary' => 'Low competition'],
            'risk' => ['level' => 'LOW', 'score' => 25, 'factors' => ['None']],
            'reasons' => ['High margin'],
            'warnings' => ['Data limited'],
            'action_plan' => ['Order sample'],
            'data_truth_summary' => ['observed_data_available' => true, 'missing_critical_data' => []],
        ];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-test-123',
                'object' => 'chat.completion',
                'created' => time(),
                'model' => 'gpt-4o-mini',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => json_encode($sampleStructured),
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 250,
                    'completion_tokens' => 120,
                    'total_tokens' => 370,
                ],
            ], 200),
        ]);

        $provider = new OpenAIProvider(
            apiKey: 'test-openai-api-key',
            model: 'gpt-4o-mini',
            timeout: 10,
            maxRetries: 0
        );

        $result = $provider->analyze(['sample_context' => 'data']);

        $this->assertEquals('openai', $result->provider);
        $this->assertEquals('gpt-4o-mini', $result->model);
        $this->assertEquals('BUY', $result->structuredAnalysis['recommendation']['decision']);
        $this->assertEquals(370, $result->tokensUsed);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-openai-api-key')
                && $request['model'] === 'gpt-4o-mini'
                && $request['response_format'] === ['type' => 'json_object'];
        });
    }

    public function test_handles_rate_limit_429_with_retry_after(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'error' => ['message' => 'Rate limit reached for requests per minute.'],
            ], 429, ['Retry-After' => '15']),
        ]);

        $provider = new OpenAIProvider(apiKey: 'sk-test', maxRetries: 0);

        try {
            $provider->analyze(['test' => true]);
            $this->fail('Expected AIRateLimitException not thrown.');
        } catch (AIRateLimitException $e) {
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertEquals(15, $e->getRetryAfterSeconds());
        }
    }

    public function test_handles_auth_failure_401(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'error' => ['message' => 'Incorrect API key provided.'],
            ], 401),
        ]);

        $provider = new OpenAIProvider(apiKey: 'sk-invalid', maxRetries: 0);

        $this->expectException(AIAuthenticationException::class);
        $this->expectExceptionCode(401);

        $provider->analyze(['test' => true]);
    }

    public function test_handles_upstream_500_error(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response('Internal Server Error', 500),
        ]);

        $provider = new OpenAIProvider(apiKey: 'sk-test', maxRetries: 0);

        $this->expectException(AIProviderException::class);
        $this->expectExceptionCode(503);

        $provider->analyze(['test' => true]);
    }

    public function test_handles_malformed_non_json_completion(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Plain text without valid JSON structure',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $provider = new OpenAIProvider(apiKey: 'sk-test', maxRetries: 0);

        $this->expectException(AISchemaValidationException::class);
        $this->expectExceptionCode(422);

        $provider->analyze(['test' => true]);
    }
}


