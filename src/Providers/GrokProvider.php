<?php

namespace LaraFlowAI\Providers;

class GrokProvider extends BaseProvider
{
    protected function getDefaultModel(): string
    {
        return 'grok-4';
    }

    protected function getApiEndpoint(): string
    {
        return 'https://api.x.ai/v1/chat/completions';
    }

    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ];
    }

    protected function formatChatPayload(string $prompt, array $options = []): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are Grok, a chatbot inspired by the Hitchhiker\'s Guide to the Galaxy. You provide insightful and unfiltered truths with a sense of humor.'
            ]
        ];

        // Add conversation history if provided
        if (isset($options['messages']) && is_array($options['messages'])) {
            $messages = array_merge($messages, $options['messages']);
        }

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];

        return [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? 1000,
            'temperature' => $options['temperature'] ?? 0.7,
            'stream' => false,
            'top_p' => $options['top_p'] ?? 1.0,
            'frequency_penalty' => $options['frequency_penalty'] ?? 0.0,
            'presence_penalty' => $options['presence_penalty'] ?? 0.0,
        ];
    }

    protected function extractChatResponse(array $response): string
    {
        return $response['choices'][0]['message']['content'] ?? '';
    }

    protected function extractTokenUsage(array $response): array
    {
        $usage = $response['usage'] ?? [];
        return [
            'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
            'completion_tokens' => $usage['completion_tokens'] ?? 0,
            'total_tokens' => $usage['total_tokens'] ?? 0,
        ];
    }

    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        // Grok pricing (as of 2024) - update as needed
        $pricing = [
            'grok-4' => ['prompt' => 3.00, 'completion' => 15.00], // per million tokens
            'grok-3' => ['prompt' => 1.00, 'completion' => 5.00],  // per million tokens
        ];

        $modelPricing = $pricing[$this->model] ?? $pricing['grok-4'];
        
        return ($promptTokens / 1000000 * $modelPricing['prompt']) + 
               ($completionTokens / 1000000 * $modelPricing['completion']);
    }

    protected function getProviderName(): string
    {
        return 'grok';
    }

    public function getSupportedModes(): array
    {
        return ['chat']; // Grok currently only supports chat mode
    }

    public function stream(string $prompt, array $options = [], callable $callback = null): \Generator
    {
        $payload = $this->formatChatPayload($prompt, $options);
        $payload['stream'] = true;
        
        $headers = $this->getHeaders();
        
        $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
            ->timeout(120) // Grok can take longer to respond
            ->withOptions([
                'stream' => true,
            ])
            ->post($this->getApiEndpoint(), $payload);

        if (!$response->successful()) {
            throw new \Exception("Grok streaming request failed: " . $response->body());
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

    /**
     * Get available Grok models
     */
    public function getAvailableModels(): array
    {
        return [
            'grok-4' => 'Grok 4 - Latest model with enhanced reasoning and coding capabilities',
            'grok-3' => 'Grok 3 - Previous generation model',
        ];
    }

    /**
     * Check if a model is available
     */
    public function isModelAvailable(string $model): bool
    {
        $availableModels = array_keys($this->getAvailableModels());
        return in_array($model, $availableModels);
    }

    /**
     * Get model capabilities
     */
    public function getModelCapabilities(string $model = null): array
    {
        $model = $model ?? $this->model;
        
        $capabilities = [
            'grok-4' => [
                'chat_completions' => true,
                'streaming' => true,
                'function_calling' => true,
                'structured_outputs' => true,
                'coding_mode' => true,
                'voice_interface' => true,
                'content_interpretation' => true,
                'max_tokens' => 128000,
            ],
            'grok-3' => [
                'chat_completions' => true,
                'streaming' => true,
                'function_calling' => false,
                'structured_outputs' => false,
                'coding_mode' => false,
                'voice_interface' => false,
                'content_interpretation' => false,
                'max_tokens' => 32000,
            ],
        ];

        return $capabilities[$model] ?? $capabilities['grok-4'];
    }
}
