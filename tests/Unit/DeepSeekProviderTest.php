<?php

namespace LaraFlowAI\Tests\Unit;

use LaraFlowAI\Tests\TestCase;
use LaraFlowAI\Providers\DeepSeekProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;

class DeepSeekProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::shouldReceive('info')->andReturn(true);
        Log::shouldReceive('error')->andReturn(true);
    }

    public function test_deepseek_provider_can_be_created()
    {
        $config = [
            'api_key' => 'test-key',
            'model' => 'deepseek-chat'
        ];

        $provider = new DeepSeekProvider($config);

        $this->assertInstanceOf(DeepSeekProvider::class, $provider);
        $this->assertEquals('deepseek-chat', $provider->getModel());
    }

    public function test_deepseek_provider_has_correct_api_endpoint()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new DeepSeekProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getApiEndpoint');
        $method->setAccessible(true);

        $this->assertEquals('https://api.deepseek.com/v1/chat/completions', $method->invoke($provider));
    }

    public function test_deepseek_provider_formats_payload_correctly()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new DeepSeekProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('formatPayload');
        $method->setAccessible(true);

        $payload = $method->invoke($provider, 'Test prompt', [
            'max_tokens' => 500,
            'temperature' => 0.8
        ]);

        $this->assertEquals('deepseek-chat', $payload['model']);
        $this->assertEquals('Test prompt', $payload['messages'][0]['content']);
        $this->assertEquals('user', $payload['messages'][0]['role']);
        $this->assertEquals(500, $payload['max_tokens']);
        $this->assertEquals(0.8, $payload['temperature']);
        $this->assertFalse($payload['stream']);
    }

    public function test_deepseek_provider_handles_conversation_history()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new DeepSeekProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('formatPayload');
        $method->setAccessible(true);

        $conversationHistory = [
            ['role' => 'system', 'content' => 'You are a helpful assistant'],
            ['role' => 'user', 'content' => 'Previous message'],
            ['role' => 'assistant', 'content' => 'Previous response']
        ];

        $payload = $method->invoke($provider, 'Current message', [
            'conversation_history' => $conversationHistory
        ]);

        $this->assertCount(4, $payload['messages']);
        $this->assertEquals('system', $payload['messages'][0]['role']);
        $this->assertEquals('You are a helpful assistant', $payload['messages'][0]['content']);
        $this->assertEquals('user', $payload['messages'][1]['role']);
        $this->assertEquals('Previous message', $payload['messages'][1]['content']);
        $this->assertEquals('assistant', $payload['messages'][2]['role']);
        $this->assertEquals('Previous response', $payload['messages'][2]['content']);
        $this->assertEquals('user', $payload['messages'][3]['role']);
        $this->assertEquals('Current message', $payload['messages'][3]['content']);
    }

    public function test_deepseek_provider_extracts_response()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new DeepSeekProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('extractResponse');
        $method->setAccessible(true);

        $response = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Test response from DeepSeek'
                    ]
                ]
            ]
        ];

        $result = $method->invoke($provider, $response);
        $this->assertEquals('Test response from DeepSeek', $result);
    }

    public function test_deepseek_provider_extracts_token_usage()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new DeepSeekProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('extractTokenUsage');
        $method->setAccessible(true);

        $response = [
            'usage' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 50,
                'total_tokens' => 150
            ]
        ];

        $result = $method->invoke($provider, $response);
        $this->assertEquals(100, $result['prompt_tokens']);
        $this->assertEquals(50, $result['completion_tokens']);
        $this->assertEquals(150, $result['total_tokens']);
    }

    public function test_deepseek_provider_calculates_cost_correctly()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new DeepSeekProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('calculateCost');
        $method->setAccessible(true);

        // Test with deepseek-chat model (cache miss pricing)
        $cost = $method->invoke($provider, 1000, 500); // 1000 prompt tokens, 500 completion tokens
        
        // Expected: (1000 * 0.56/1000000) + (500 * 1.68/1000000) = 0.00056 + 0.00084 = 0.0014
        $expectedCost = (1000 * 0.56 / 1000000) + (500 * 1.68 / 1000000);
        $this->assertEqualsWithDelta($expectedCost, $cost, 0.00001);
    }

    public function test_deepseek_provider_returns_available_models()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new DeepSeekProvider($config);

        $models = $provider->getAvailableModels();

        $this->assertArrayHasKey('deepseek-chat', $models);
        $this->assertArrayHasKey('deepseek-reasoner', $models);
        $this->assertEquals('DeepSeek Chat (V3)', $models['deepseek-chat']['name']);
        $this->assertEquals(128000, $models['deepseek-chat']['context_length']);
        $this->assertEquals('DeepSeek Reasoner (R1)', $models['deepseek-reasoner']['name']);
        $this->assertEquals(64000, $models['deepseek-reasoner']['context_length']);
    }

    public function test_deepseek_provider_checks_model_availability()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new DeepSeekProvider($config);

        $this->assertTrue($provider->isModelAvailable('deepseek-chat'));
        $this->assertTrue($provider->isModelAvailable('deepseek-reasoner'));
        $this->assertFalse($provider->isModelAvailable('invalid-model'));
    }

    public function test_deepseek_provider_returns_model_capabilities()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new DeepSeekProvider($config);

        $capabilities = $provider->getModelCapabilities('deepseek-chat');
        $this->assertArrayHasKey('name', $capabilities);
        $this->assertArrayHasKey('context_length', $capabilities);
        $this->assertArrayHasKey('features', $capabilities);
        $this->assertArrayHasKey('description', $capabilities);

        $capabilities = $provider->getModelCapabilities('invalid-model');
        $this->assertEmpty($capabilities);
    }

    public function test_deepseek_provider_uses_correct_headers()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new DeepSeekProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($provider);

        $this->assertEquals('Bearer test-key', $headers['Authorization']);
        $this->assertEquals('application/json', $headers['Content-Type']);
    }

    public function test_deepseek_provider_generates_response()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new DeepSeekProvider($config);

        $mockResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Test response from DeepSeek'
                    ]
                ]
            ],
            'usage' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 50,
                'total_tokens' => 150
            ]
        ];

        // Clear any existing fakes and set new one
        Http::clearResolvedInstances();
        Http::fake([
            'api.deepseek.com/*' => Http::response($mockResponse, 200, ['Content-Type' => 'application/json'])
        ]);

        $result = $provider->generate('Test prompt');

        $this->assertEquals('Test response from DeepSeek', $result);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.deepseek.com/v1/chat/completions' &&
                   $request->header('Authorization')[0] === 'Bearer test-key' &&
                   $request->header('Content-Type')[0] === 'application/json';
        });
    }
}
