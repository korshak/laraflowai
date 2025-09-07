<?php

namespace LaraFlowAI\Contracts;

/**
 * ProviderContract interface defines the contract for LLM providers.
 * 
 * This interface ensures that all LLM providers implement the same
 * basic functionality for generating responses, streaming, and
 * configuration management.
 * 
 * @package LaraFlowAI\Contracts
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
interface ProviderContract
{
    /**
     * Generate a response from the LLM provider.
     * 
     * @param string $prompt The input prompt
     * @param array<string, mixed> $options Additional options
     * @return string The generated response
     * 
     * @throws \Exception If the request fails
     */
    public function generate(string $prompt, array $options = []): string;

    /**
     * Generate a response with streaming support.
     * 
     * @param string $prompt The input prompt
     * @param array<string, mixed> $options Additional options
     * @param callable|null $callback Optional callback for each chunk
     * @return \Generator Generator yielding response chunks
     */
    public function stream(string $prompt, array $options = [], ?callable $callback = null): \Generator;

    /**
     * Get the provider configuration.
     * 
     * @return array<string, mixed> The provider configuration
     */
    public function getConfig(): array;

    /**
     * Set the model for this provider.
     * 
     * @param string $model The model name
     * @return self Returns the provider instance for method chaining
     */
    public function setModel(string $model): self;

    /**
     * Get the current model.
     * 
     * @return string The current model name
     */
    public function getModel(): string;
}
