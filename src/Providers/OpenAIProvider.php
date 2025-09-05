<?php

namespace LaraFlowAI\Providers;

/**
 * OpenAIProvider class provides integration with OpenAI's API.
 * 
 * This provider supports OpenAI's GPT models for chat completions,
 * text completions, and embeddings. It handles authentication,
 * request formatting, and response processing for OpenAI's API.
 * 
 * @package LaraFlowAI\Providers
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class OpenAIProvider extends BaseProvider
{
    /**
     * Get the default model for OpenAI provider.
     * 
     * @return string The default model name
     */
    protected function getDefaultModel(): string
    {
        return 'gpt-4';
    }

    /**
     * Get the API endpoint for OpenAI provider.
     * 
     * @return string The API endpoint URL
     */
    protected function getApiEndpoint(): string
    {
        return 'https://api.openai.com/v1/chat/completions';
    }

    /**
     * Get the API endpoint for a specific mode.
     * 
     * @param string $mode The operation mode
     * @return string The API endpoint URL for the mode
     */
    protected function getApiEndpointForMode(string $mode): string
    {
        return match($mode) {
            'completion' => 'https://api.openai.com/v1/completions',
            'embedding' => 'https://api.openai.com/v1/embeddings',
            'chat' => 'https://api.openai.com/v1/chat/completions',
            default => 'https://api.openai.com/v1/chat/completions'
        };
    }

    /**
     * Get the headers for OpenAI API requests.
     * 
     * @return array<string, string> The request headers
     */
    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Format payload for OpenAI chat mode.
     * 
     * @param string $prompt The input prompt
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed> The formatted chat payload
     */
    protected function formatChatPayload(string $prompt, array $options = []): array
    {
        return [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $options['max_tokens'] ?? 1000,
            'temperature' => $options['temperature'] ?? 0.7,
            'stream' => false,
        ];
    }

    /**
     * Extract response from OpenAI chat mode response.
     * 
     * @param array<string, mixed> $response The API response
     * @return string The extracted chat response
     */
    protected function extractChatResponse(array $response): string
    {
        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Extract token usage from OpenAI API response.
     * 
     * @param array<string, mixed> $response The API response
     * @return array<string, int> The token usage data
     */
    protected function extractTokenUsage(array $response): array
    {
        $usage = $response['usage'] ?? [];
        return [
            'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
            'completion_tokens' => $usage['completion_tokens'] ?? 0,
            'total_tokens' => $usage['total_tokens'] ?? 0,
        ];
    }

    /**
     * Calculate cost based on OpenAI token usage.
     * 
     * @param int $promptTokens Number of prompt tokens
     * @param int $completionTokens Number of completion tokens
     * @return float The calculated cost in USD
     */
    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        // OpenAI pricing (as of 2025) - update as needed
        $pricing = [
            'gpt-5' => ['prompt' => 1.25 / 1000000, 'completion' => 10.00 / 1000000],
            'gpt-5-mini' => ['prompt' => 0.25 / 1000000, 'completion' => 2.00 / 1000000],
            'gpt-5-nano' => ['prompt' => 0.05 / 1000000, 'completion' => 0.40 / 1000000],
            'o3-pro' => ['prompt' => 20.00 / 1000000, 'completion' => 80.00 / 1000000],
            'gpt-4o' => ['prompt' => 2.50 / 1000000, 'completion' => 10.00 / 1000000],
            'gpt-4o-mini' => ['prompt' => 0.15 / 1000000, 'completion' => 0.60 / 1000000],
        ];

        $modelPricing = $pricing[$this->model] ?? $pricing['gpt-3.5-turbo'];
        
        return ($promptTokens / 1000 * $modelPricing['prompt']) + 
               ($completionTokens / 1000 * $modelPricing['completion']);
    }

    /**
     * Get provider name for tracking.
     * 
     * @return string The provider name
     */
    protected function getProviderName(): string
    {
        return 'openai';
    }

    /**
     * Get supported modes for OpenAI provider.
     * 
     * @return array<string> Array of supported modes
     */
    public function getSupportedModes(): array
    {
        return ['chat', 'completion', 'embedding'];
    }

    /**
     * Format payload for OpenAI completion mode.
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
     * Format payload for OpenAI embedding mode.
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
     * Extract response from OpenAI completion mode response.
     * 
     * @param array<string, mixed> $response The API response
     * @return string The extracted completion response
     */
    protected function extractCompletionResponse(array $response): string
    {
        return $response['choices'][0]['text'] ?? '';
    }

    /**
     * Extract response from OpenAI embedding mode response.
     * 
     * @param array<string, mixed> $response The API response
     * @return array<int, float> The extracted embedding vector
     */
    protected function extractEmbeddingResponse(array $response): array
    {
        return $response['data'][0]['embedding'] ?? [];
    }

    /**
     * Stream a response using OpenAI's streaming API.
     * 
     * @param string $prompt The input prompt
     * @param array<string, mixed> $options Additional options
     * @param callable|null $callback Optional callback for each chunk
     * @return \Generator Generator yielding response chunks
     * 
     * @throws \Exception If the streaming request fails
     */
    public function stream(string $prompt, array $options = [], callable $callback = null): \Generator
    {
        $payload = $this->formatPayload($prompt, $options);
        $payload['stream'] = true;
        
        $headers = $this->getHeaders();
        
        $response = Http::withHeaders($headers)
            ->timeout(60)
            ->withOptions([
                'stream' => true,
            ])
            ->post($this->getApiEndpoint(), $payload);

        if (!$response->successful()) {
            throw new \Exception("OpenAI streaming request failed: " . $response->body());
        }

        $buffer = '';
        foreach ($response->stream() as $chunk) {
            $buffer .= $chunk;
            
            // Process complete lines
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                
                if (str_starts_with($line, 'data: ')) {
                    $data = trim(substr($line, 6));
                    
                    if ($data === '[DONE]') {
                        break 2;
                    }
                    
                    $decoded = json_decode($data, true);
                    if (isset($decoded['choices'][0]['delta']['content'])) {
                        $content = $decoded['choices'][0]['delta']['content'];
                        
                        if ($callback) {
                            $callback($content);
                        }
                        
                        yield $content;
                    }
                }
            }
        }
    }
}
