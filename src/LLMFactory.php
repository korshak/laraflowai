<?php

namespace LaraFlowAI;

use LaraFlowAI\Contracts\ProviderContract;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class LLMFactory extends Manager
{
    /**
     * The default provider name
     */
    protected string $defaultProvider;

    /**
     * Custom provider resolvers
     */
    protected array $customProviders = [];

    public function __construct($app, string $defaultProvider = 'openai')
    {
        parent::__construct($app);
        $this->defaultProvider = $defaultProvider;
    }

    /**
     * Get the default provider name
     */
    public function getDefaultProvider(): string
    {
        return $this->defaultProvider;
    }

    /**
     * Set the default provider
     */
    public function setDefaultProvider(string $provider): self
    {
        $this->defaultProvider = $provider;
        return $this;
    }

    /**
     * Create a provider instance
     */
    public function createProvider(string $driver): ProviderContract
    {
        $config = $this->getProviderConfig($driver);
        
        if (isset($this->customProviders[$driver])) {
            return $this->customProviders[$driver]($config);
        }

        $providerClass = $config['driver'] ?? null;
        
        if (!$providerClass || !class_exists($providerClass)) {
            throw new InvalidArgumentException("Provider driver [{$driver}] not found.");
        }

        return new $providerClass($config);
    }

    /**
     * Register a custom provider
     */
    public function extend($driver, \Closure $callback)
    {
        $this->customProviders[$driver] = $callback;
        return $this;
    }

    /**
     * Get provider configuration
     */
    protected function getProviderConfig(string $driver): array
    {
        $config = config("laraflowai.providers.{$driver}", []);
        
        if (empty($config)) {
            $availableProviders = array_keys(config('laraflowai.providers', []));
            throw new InvalidArgumentException("Provider [{$driver}] not configured. Available providers: " . implode(', ', $availableProviders));
        }

        return $config;
    }

    /**
     * Get all available providers
     */
    public function getAvailableProviders(): array
    {
        $configProviders = array_keys($this->app['config']['laraflowai.providers'] ?? []);
        $customProviders = array_keys($this->customProviders);
        
        return array_unique(array_merge($configProviders, $customProviders));
    }

    /**
     * Check if a provider exists
     */
    public function hasProvider(string $driver): bool
    {
        return isset($this->app['config']['laraflowai.providers'][$driver]) 
            || isset($this->customProviders[$driver]);
    }

    /**
     * Create OpenAI provider
     */
    protected function createOpenaiDriver(): ProviderContract
    {
        $config = $this->getProviderConfig('openai');
        return new \LaraFlowAI\Providers\OpenAIProvider($config);
    }

    /**
     * Create Anthropic provider
     */
    protected function createAnthropicDriver(): ProviderContract
    {
        $config = $this->getProviderConfig('anthropic');
        return new \LaraFlowAI\Providers\AnthropicProvider($config);
    }

    /**
     * Create Ollama provider
     */
    protected function createOllamaDriver(): ProviderContract
    {
        $config = $this->getProviderConfig('ollama');
        return new \LaraFlowAI\Providers\OllamaProvider($config);
    }

    /**
     * Create Groq provider
     */
    protected function createGroqDriver(): ProviderContract
    {
        $config = $this->getProviderConfig('groq');
        return new \LaraFlowAI\Providers\GroqProvider($config);
    }

    /**
     * Create Gemini provider
     */
    protected function createGeminiDriver(): ProviderContract
    {
        $config = $this->getProviderConfig('gemini');
        return new \LaraFlowAI\Providers\GeminiProvider($config);
    }

    /**
     * Create Grok provider
     */
    protected function createGrokDriver(): ProviderContract
    {
        $config = $this->getProviderConfig('grok');
        return new \LaraFlowAI\Providers\GrokProvider($config);
    }

    /**
     * Create DeepSeek provider
     */
    protected function createDeepseekDriver(): ProviderContract
    {
        $config = $this->getProviderConfig('deepseek');
        return new \LaraFlowAI\Providers\DeepSeekProvider($config);
    }

    /**
     * Get the default driver name
     */
    public function getDefaultDriver(): string
    {
        return $this->defaultProvider;
    }
}
