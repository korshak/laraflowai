<?php

namespace LaraFlowAI\Providers;

use Illuminate\Support\Facades\Http;

class DeepSeekProvider extends BaseProvider
{
    protected function getDefaultModel(): string
    {
        return 'deepseek-chat';
    }

    protected function getApiEndpoint(): string
    {
        return 'https://api.deepseek.com/v1/chat/completions';
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
        $messages = [];
        
        // Handle conversation history if provided
        if (isset($options['conversation_history']) && is_array($options['conversation_history'])) {
            $messages = $options['conversation_history'];
        }
        
        // Add current prompt
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
            'stream_options' => [
                'include_usage' => true
            ]
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


    protected function getProviderName(): string
    {
        return 'deepseek';
    }


    public function getAvailableModels(): array
    {
        return [
            'deepseek-chat' => [
                'name' => 'DeepSeek Chat (V3)',
                'context_length' => 128000,
                'features' => ['json_output', 'function_calling', 'chat_prefix_completion', 'fim_completion'],
                'description' => 'Advanced conversational AI with 128K context length'
            ],
            'deepseek-reasoner' => [
                'name' => 'DeepSeek Reasoner (R1)',
                'context_length' => 64000,
                'features' => ['json_output', 'chat_prefix_completion'],
                'description' => 'Reasoning-focused model with 64K context length'
            ]
        ];
    }

    public function isModelAvailable(string $model): bool
    {
        $availableModels = $this->getAvailableModels();
        return isset($availableModels[$model]);
    }

    public function getModelCapabilities(string $model): array
    {
        $availableModels = $this->getAvailableModels();
        return $availableModels[$model] ?? [];
    }

    public function getSupportedModes(): array
    {
        return ['chat']; // DeepSeek currently only supports chat mode
    }

    public function stream(string $prompt, array $options = [], ?callable $callback = null): \Generator
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
            throw new \Exception("DeepSeek streaming request failed: " . $response->body());
        }

        $buffer = '';
        // Use the response body as a stream
        $body = $response->body();
        $lines = explode("\n", $body);
        
        foreach ($lines as $chunk) {
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
