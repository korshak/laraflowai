<?php

namespace LaraFlowAI\Tests\Unit;

use LaraFlowAI\Tests\TestCase;
use LaraFlowAI\Providers\OpenAIProvider;
use LaraFlowAI\Providers\DeepSeekProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProviderModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::shouldReceive('info')->andReturn(true);
        Log::shouldReceive('error')->andReturn(true);
    }

    public function test_openai_provider_supports_multiple_modes()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new OpenAIProvider($config);

        $supportedModes = $provider->getSupportedModes();
        
        $this->assertContains('chat', $supportedModes);
        $this->assertContains('completion', $supportedModes);
        $this->assertContains('embedding', $supportedModes);
        $this->assertCount(3, $supportedModes);
    }

    public function test_deepseek_provider_supports_chat_mode_only()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new DeepSeekProvider($config);

        $supportedModes = $provider->getSupportedModes();
        
        $this->assertContains('chat', $supportedModes);
        $this->assertCount(1, $supportedModes);
    }

    public function test_provider_mode_can_be_set_and_retrieved()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new OpenAIProvider($config);

        // Test default mode
        $this->assertEquals('chat', $provider->getMode());

        // Test setting mode
        $provider->setMode('completion');
        $this->assertEquals('completion', $provider->getMode());

        $provider->setMode('embedding');
        $this->assertEquals('embedding', $provider->getMode());
    }

    public function test_provider_mode_can_be_set_in_config()
    {
        $config = [
            'api_key' => 'test-key',
            'mode' => 'completion'
        ];
        $provider = new OpenAIProvider($config);

        $this->assertEquals('completion', $provider->getMode());
    }

    public function test_provider_checks_mode_support()
    {
        $config = ['api_key' => 'test-key'];
        $openaiProvider = new OpenAIProvider($config);
        $deepseekProvider = new DeepSeekProvider($config);

        // OpenAI supports multiple modes
        $this->assertTrue($openaiProvider->isModeSupported('chat'));
        $this->assertTrue($openaiProvider->isModeSupported('completion'));
        $this->assertTrue($openaiProvider->isModeSupported('embedding'));
        $this->assertFalse($openaiProvider->isModeSupported('invalid'));

        // DeepSeek only supports chat
        $this->assertTrue($deepseekProvider->isModeSupported('chat'));
        $this->assertFalse($deepseekProvider->isModeSupported('completion'));
        $this->assertFalse($deepseekProvider->isModeSupported('embedding'));
    }

    public function test_openai_provider_uses_correct_endpoints_for_different_modes()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new OpenAIProvider($config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getApiEndpointForMode');
        $method->setAccessible(true);

        $this->assertEquals('https://api.openai.com/v1/chat/completions', $method->invoke($provider, 'chat'));
        $this->assertEquals('https://api.openai.com/v1/completions', $method->invoke($provider, 'completion'));
        $this->assertEquals('https://api.openai.com/v1/embeddings', $method->invoke($provider, 'embedding'));
    }

    public function test_openai_provider_formats_completion_payload()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new OpenAIProvider($config);
        $provider->setMode('completion');

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('formatCompletionPayload');
        $method->setAccessible(true);

        $payload = $method->invoke($provider, 'Test prompt', [
            'max_tokens' => 500,
            'temperature' => 0.8
        ]);

        $this->assertEquals('gpt-4', $payload['model']);
        $this->assertEquals('Test prompt', $payload['prompt']);
        $this->assertEquals(500, $payload['max_tokens']);
        $this->assertEquals(0.8, $payload['temperature']);
        $this->assertArrayNotHasKey('messages', $payload);
    }

    public function test_openai_provider_formats_embedding_payload()
    {
        $config = ['api_key' => 'test-key'];
        $provider = new OpenAIProvider($config);
        $provider->setMode('embedding');

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('formatEmbeddingPayload');
        $method->setAccessible(true);

        $payload = $method->invoke($provider, 'Text to embed', []);

        $this->assertEquals('gpt-4', $payload['model']);
        $this->assertEquals('Text to embed', $payload['input']);
        $this->assertArrayNotHasKey('messages', $payload);
        $this->assertArrayNotHasKey('prompt', $payload);
    }
}
