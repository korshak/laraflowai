<?php

namespace LaraFlowAI;

use LaraFlowAI\Contracts\ProviderContract;
use LaraFlowAI\Contracts\MemoryContract;
use Illuminate\Contracts\Foundation\Application;

/**
 * LaraFlowAIManager class provides a centralized interface for managing AI components.
 * 
 * The manager acts as a factory and service locator for creating agents, crews,
 * flows, and tasks. It manages LLM providers, memory systems, and provides
 * a unified API for the LaraFlowAI package.
 * 
 * @package LaraFlowAI
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class LaraFlowAIManager
{
    /**
     * The Laravel application instance.
     * 
     * @var Application
     */
    protected Application $app;

    /**
     * The LLM factory instance.
     * 
     * @var LLMFactory
     */
    protected LLMFactory $llmFactory;

    /**
     * The memory contract instance.
     * 
     * @var MemoryContract
     */
    protected MemoryContract $memory;

    /**
     * Create a new LaraFlowAIManager instance.
     * 
     * @param Application $app The Laravel application instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->llmFactory = $app['laraflowai.llm'];
        $this->memory = $app[MemoryContract::class];
    }

    /**
     * Get an LLM provider.
     * 
     * @param string|null $driver The provider driver name
     * @return ProviderContract The LLM provider instance
     */
    public function llm(?string $driver = null): ProviderContract
    {
        return $this->llmFactory->driver($driver);
    }

    /**
     * Create an agent.
     * 
     * @param string $role The agent role
     * @param string $goal The agent goal
     * @param string|null $provider The provider driver name
     * @return Agent A new Agent instance
     */
    public function agent(string $role, string $goal, ?string $provider = null): Agent
    {
        $llmProvider = $provider ? $this->llm($provider) : $this->llm();
        
        return new Agent($role, $goal, $llmProvider, $this->memory);
    }

    /**
     * Create a task.
     * 
     * @param string $description The task description
     * @param array<string, mixed> $config Optional configuration array
     * @return Task A new Task instance
     */
    public function task(string $description, array $config = []): Task
    {
        return new Task($description, $config);
    }

    /**
     * Create a crew.
     * 
     * @param array<string, mixed> $config Optional configuration array
     * @return Crew A new Crew instance
     */
    public function crew(array $config = []): Crew
    {
        return new Crew($this->memory, $config);
    }

    /**
     * Create a flow.
     * 
     * @param array<string, mixed> $config Optional configuration array
     * @return Flow A new Flow instance
     */
    public function flow(array $config = []): Flow
    {
        return new Flow($this->memory, $config);
    }

    /**
     * Get memory manager.
     * 
     * @return MemoryContract The memory contract instance
     */
    public function memory(): MemoryContract
    {
        return $this->memory;
    }

    /**
     * Extend with custom provider.
     * 
     * @param string $driver The provider driver name
     * @param callable $resolver The provider resolver function
     */
    public function extend(string $driver, callable $resolver): void
    {
        $this->llmFactory->extend($driver, $resolver);
    }

    /**
     * Get available providers.
     * 
     * @return array<string> Array of available provider names
     */
    public function getAvailableProviders(): array
    {
        return $this->llmFactory->getAvailableProviders();
    }

    /**
     * Check if provider exists.
     * 
     * @param string $driver The provider driver name
     * @return bool True if provider exists, false otherwise
     */
    public function hasProvider(string $driver): bool
    {
        return $this->llmFactory->hasProvider($driver);
    }

    /**
     * Get default provider.
     * 
     * @return string The default provider name
     */
    public function getDefaultProvider(): string
    {
        return $this->llmFactory->getDefaultProvider();
    }

    /**
     * Set default provider.
     * 
     * @param string $provider The provider name to set as default
     */
    public function setDefaultProvider(string $provider): void
    {
        $this->llmFactory->setDefaultProvider($provider);
    }

    /**
     * Get LLM factory.
     * 
     * @return LLMFactory The LLM factory instance
     */
    public function getLLMFactory(): LLMFactory
    {
        return $this->llmFactory;
    }
}
