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
    public function extend(string $driver, callable $resolver): self
    {
        $this->customProviders[$driver] = $resolver;
        return $this;
    }

    /**
     * Get provider configuration
     */
    protected function getProviderConfig(string $driver): array
    {
        $config = $this->app['config']['laraflowai.providers'][$driver] ?? [];
        
        if (empty($config)) {
            throw new InvalidArgumentException("Provider [{$driver}] not configured.");
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
    protected function createOpenaiDriver(array $config): ProviderContract
    {
        return new \LaraFlowAI\Providers\OpenAIProvider($config);
    }

    /**
     * Create Anthropic provider
     */
    protected function createAnthropicDriver(array $config): ProviderContract
    {
        return new \LaraFlowAI\Providers\AnthropicProvider($config);
    }

    /**
     * Create Ollama provider
     */
    protected function createOllamaDriver(array $config): ProviderContract
    {
        return new \LaraFlowAI\Providers\OllamaProvider($config);
    }

    /**
     * Create Groq provider
     */
    protected function createGroqDriver(array $config): ProviderContract
    {
        return new \LaraFlowAI\Providers\GroqProvider($config);
    }

    /**
     * Create Gemini provider
     */
    protected function createGeminiDriver(array $config): ProviderContract
    {
        return new \LaraFlowAI\Providers\GeminiProvider($config);
    }

    /**
     * Get the default driver name
     */
    public function getDefaultDriver(): string
    {
        return $this->defaultProvider;
    }
}
