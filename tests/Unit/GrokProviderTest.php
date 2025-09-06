<?php

namespace LaraFlowAI\Tests\Unit;

use LaraFlowAI\Tests\TestCase;
use LaraFlowAI\Providers\GrokProvider;
use Mockery;

class GrokProviderTest extends TestCase
{
    public function test_grok_provider_can_be_created()
    {
        $config = [
            'api_key' => 'test-key',
            'model' => 'grok-4',
        ];

        $provider = new GrokProvider($config);

        $this->assertInstanceOf(GrokProvider::class, $provider);
        $this->assertEquals('grok-4', $provider->getModel());
    }

    public function test_grok_provider_has_correct_api_endpoint()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new GrokProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getApiEndpoint');
        $method->setAccessible(true);

        $this->assertEquals('https://api.x.ai/v1/chat/completions', $method->invoke($provider));
    }

    public function test_grok_provider_formats_payload_correctly()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new GrokProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('formatPayload');
        $method->setAccessible(true);

        $payload = $method->invoke($provider, 'Hello, Grok!', ['max_tokens' => 500]);

        $this->assertEquals('grok-4', $payload['model']);
        $this->assertEquals(500, $payload['max_tokens']);
        $this->assertEquals(0.7, $payload['temperature']);
        $this->assertFalse($payload['stream']);
        $this->assertCount(2, $payload['messages']);
        $this->assertEquals('system', $payload['messages'][0]['role']);
        $this->assertEquals('user', $payload['messages'][1]['role']);
        $this->assertEquals('Hello, Grok!', $payload['messages'][1]['content']);
    }

    public function test_grok_provider_extracts_response_correctly()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new GrokProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('extractResponse');
        $method->setAccessible(true);

        $response = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Hello! I am Grok, your AI assistant.'
                    ]
                ]
            ]
        ];

        $content = $method->invoke($provider, $response);
        $this->assertEquals('Hello! I am Grok, your AI assistant.', $content);
    }

    public function test_grok_provider_extracts_token_usage()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new GrokProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('extractTokenUsage');
        $method->setAccessible(true);

        $response = [
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'total_tokens' => 30,
            ]
        ];

        $usage = $method->invoke($provider, $response);
        $this->assertEquals(10, $usage['prompt_tokens']);
        $this->assertEquals(20, $usage['completion_tokens']);
        $this->assertEquals(30, $usage['total_tokens']);
    }


    public function test_grok_provider_returns_available_models()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new GrokProvider($config);

        $models = $provider->getAvailableModels();

        $this->assertArrayHasKey('grok-4', $models);
        $this->assertArrayHasKey('grok-3', $models);
        $this->assertStringContainsString('Grok 4', $models['grok-4']);
        $this->assertStringContainsString('Grok 3', $models['grok-3']);
    }

    public function test_grok_provider_checks_model_availability()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new GrokProvider($config);

        $this->assertTrue($provider->isModelAvailable('grok-4'));
        $this->assertTrue($provider->isModelAvailable('grok-3'));
        $this->assertFalse($provider->isModelAvailable('gpt-4'));
    }

    public function test_grok_provider_returns_model_capabilities()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new GrokProvider($config);

        $capabilities = $provider->getModelCapabilities('grok-4');

        $this->assertTrue($capabilities['chat_completions']);
        $this->assertTrue($capabilities['streaming']);
        $this->assertTrue($capabilities['function_calling']);
        $this->assertTrue($capabilities['structured_outputs']);
        $this->assertTrue($capabilities['coding_mode']);
        $this->assertEquals(128000, $capabilities['max_tokens']);

        $capabilities3 = $provider->getModelCapabilities('grok-3');
        $this->assertTrue($capabilities3['chat_completions']);
        $this->assertTrue($capabilities3['streaming']);
        $this->assertFalse($capabilities3['function_calling']);
        $this->assertFalse($capabilities3['structured_outputs']);
        $this->assertEquals(32000, $capabilities3['max_tokens']);
    }

    public function test_grok_provider_handles_conversation_history()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new GrokProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('formatPayload');
        $method->setAccessible(true);

        $options = [
            'messages' => [
                ['role' => 'user', 'content' => 'Previous message'],
                ['role' => 'assistant', 'content' => 'Previous response'],
            ]
        ];

        $payload = $method->invoke($provider, 'Current message', $options);

        $this->assertCount(4, $payload['messages']); // system + 2 conversation messages + current message
        $this->assertEquals('system', $payload['messages'][0]['role']);
        $this->assertEquals('user', $payload['messages'][1]['role']);
        $this->assertEquals('assistant', $payload['messages'][2]['role']);
        $this->assertEquals('user', $payload['messages'][3]['role']);
        $this->assertEquals('Previous message', $payload['messages'][1]['content']);
        $this->assertEquals('Current message', $payload['messages'][3]['content']);
    }

    public function test_grok_provider_uses_correct_headers()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new GrokProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($provider);

        $this->assertEquals('Bearer test-key', $headers['Authorization']);
        $this->assertEquals('application/json', $headers['Content-Type']);
    }
}
