<?php

namespace LaraFlowAI\Providers;

use LaraFlowAI\Contracts\ProviderContract;
use LaraFlowAI\Metrics\TokenUsageTracker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BaseProvider abstract class provides common functionality for all LLM providers.
 * 
 * This class implements the ProviderContract interface and provides a foundation
 * for all specific provider implementations. It handles common functionality
 * like token tracking, request formatting, and response processing.
 * 
 * @package LaraFlowAI\Providers
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
abstract class BaseProvider implements ProviderContract
{
    /**
     * Provider configuration array.
     * 
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * The model to use for requests.
     * 
     * @var string
     */
    protected string $model;

    /**
     * The mode of operation (chat, completion, embedding).
     * 
     * @var string
     */
    protected string $mode;

    /**
     * Token usage tracker instance.
     * 
     * @var TokenUsageTracker
     */
    protected TokenUsageTracker $tokenTracker;

    /**
     * Create a new BaseProvider instance.
     * 
     * @param array<string, mixed> $config Provider configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->model = $config['model'] ?? $this->getDefaultModel();
        $this->mode = $config['mode'] ?? 'chat'; // Default to chat mode
        $this->tokenTracker = new TokenUsageTracker();
    }

    /**
     * Get the default model for this provider.
     * 
     * @return string The default model name
     */
    abstract protected function getDefaultModel(): string;

    /**
     * Get the API endpoint for this provider.
     * 
     * @return string The API endpoint URL
     */
    abstract protected function getApiEndpoint(): string;

    /**
     * Get the API endpoint for a specific mode.
     * 
     * @param string $mode The operation mode
     * @return string The API endpoint URL for the mode
     */
    protected function getApiEndpointForMode(string $mode): string
    {
        return $this->getApiEndpoint();
    }

    /**
     * Get the headers for API requests.
     * 
     * @return array<string, string> The request headers
     */
    abstract protected function getHeaders(): array;

    /**
     * Format the request payload.
     * 
     * @param string $prompt The input prompt
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed> The formatted payload
     */
    protected function formatPayload(string $prompt, array $options = []): array
    {
        switch ($this->mode) {
            case 'completion':
                return $this->formatCompletionPayload($prompt, $options);
            case 'embedding':
                return $this->formatEmbeddingPayload($prompt, $options);
            case 'chat':
            default:
                return $this->formatChatPayload($prompt, $options);
        }
    }

    /**
     * Format payload for chat mode (default implementation).
     * 
     * @param string $prompt The input prompt
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed> The formatted chat payload
     */
    abstract protected function formatChatPayload(string $prompt, array $options = []): array;

    /**
     * Extract response from API response.
     * 
     * @param array<string, mixed> $response The API response
     * @return string|array The extracted response
     */
    protected function extractResponse(array $response): string|array
    {
        switch ($this->mode) {
            case 'completion':
                return $this->extractCompletionResponse($response);
            case 'embedding':
                return $this->extractEmbeddingResponse($response);
            case 'chat':
            default:
                return $this->extractChatResponse($response);
        }
    }

    /**
     * Extract response from chat mode response.
     * 
     * @param array<string, mixed> $response The API response
     * @return string The extracted chat response
     */
    abstract protected function extractChatResponse(array $response): string;

    /**
     * Extract token usage from API response.
     * 
     * @param array<string, mixed> $response The API response
     * @return array<string, int> The token usage data
     */
    abstract protected function extractTokenUsage(array $response): array;


    /**
     * Get provider name for tracking.
     * 
     * @return string The provider name
     */
    abstract protected function getProviderName(): string;

    /**
     * Generate a response using the provider.
     * 
     * @param string $prompt The input prompt
     * @param array<string, mixed> $options Additional options
     * @return string The generated response
     * 
     * @throws \Exception If the request fails
     */
    public function generate(string $prompt, array $options = []): string
    {
        try {
            $payload = $this->formatPayload($prompt, $options);
            $headers = $this->getHeaders();
            $endpoint = $this->getApiEndpointForMode($this->mode);
            
            Log::info('LaraFlowAI: Sending request to provider', [
                'provider' => static::class,
                'model' => $this->model,
                'mode' => $this->mode,
                'prompt_length' => strlen($prompt)
            ]);

            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->post($endpoint, $payload);

            if (!$response->successful()) {
                throw new \Exception("Provider request failed: " . $response->body());
            }

            $responseData = $response->json();
            $result = $this->extractResponse($responseData);
            
            // Track token usage
            $tokenUsage = $this->extractTokenUsage($responseData);
            if (!empty($tokenUsage)) {
                $this->tokenTracker->track(
                    $this->getProviderName(),
                    $this->model,
                    $tokenUsage['prompt_tokens'] ?? 0,
                    $tokenUsage['completion_tokens'] ?? 0,
                    null, // No cost calculation
                    ['prompt_length' => strlen($prompt)]
                );
            }
            
            $responseLength = is_string($result) ? strlen($result) : (is_array($result) ? count($result) : 0);
            
            Log::info('LaraFlowAI: Received response from provider', [
                'provider' => static::class,
                'mode' => $this->mode,
                'response_length' => $responseLength,
                'token_usage' => $tokenUsage ?? null
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('LaraFlowAI: Provider request failed', [
                'provider' => static::class,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Stream a response using the provider.
     * 
     * @param string $prompt The input prompt
     * @param array<string, mixed> $options Additional options
     * @param callable|null $callback Optional callback for each chunk
     * @return \Generator Generator yielding response chunks
     */
    public function stream(string $prompt, array $options = [], callable $callback = null): \Generator
    {
        // Default implementation - override in specific providers
        $response = $this->generate($prompt, $options);
        
        if ($callback) {
            $callback($response);
        }
        
        yield $response;
    }

    /**
     * Get provider configuration.
     * 
     * @return array<string, mixed> The provider configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set the model to use.
     * 
     * @param string $model The model name
     * @return self Returns the provider instance for method chaining
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Get the current model.
     * 
     * @return string The current model name
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Set the operation mode.
     * 
     * @param string $mode The operation mode
     * @return self Returns the provider instance for method chaining
     */
    public function setMode(string $mode): self
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Get the current operation mode.
     * 
     * @return string The current operation mode
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Get supported modes for this provider.
     * 
     * @return array<string> Array of supported modes
     */
    public function getSupportedModes(): array
    {
        return ['chat']; // Default to chat mode
    }

    /**
     * Check if a mode is supported.
     * 
     * @param string $mode The mode to check
     * @return bool True if mode is supported, false otherwise
     */
    public function isModeSupported(string $mode): bool
    {
        return in_array($mode, $this->getSupportedModes());
    }

    /**
     * Format payload for completion mode (text completion).
     * 
     * @param string $prompt The input prompt
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed> The formatted completion payload
     */
    protected function formatCompletionPayload(string $prompt, array $options = []): array
    {
        return [
            'model' => $this->model,
            'prompt' => $prompt,
            'max_tokens' => $options['max_tokens'] ?? 1000,
            'temperature' => $options['temperature'] ?? 0.7,
        ];
    }

    /**
     * Format payload for embedding mode.
     * 
     * @param string $text The input text
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed> The formatted embedding payload
     */
    protected function formatEmbeddingPayload(string $text, array $options = []): array
    {
        return [
            'model' => $this->model,
            'input' => $text,
        ];
    }

    /**
     * Extract response from completion mode response.
     * 
     * @param array<string, mixed> $response The API response
     * @return string The extracted completion response
     */
    protected function extractCompletionResponse(array $response): string
    {
        return $response['choices'][0]['text'] ?? '';
    }

    /**
     * Extract response from embedding mode response.
     * 
     * @param array<string, mixed> $response The API response
     * @return array<int, float> The extracted embedding vector
     */
    protected function extractEmbeddingResponse(array $response): array
    {
        return $response['data'][0]['embedding'] ?? [];
    }
}
