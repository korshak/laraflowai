<?php

namespace LaraFlowAI\Providers;

class OllamaProvider extends BaseProvider
{
    protected function getDefaultModel(): string
    {
        return 'mistral';
    }

    protected function getApiEndpoint(): string
    {
        $host = $this->config['host'] ?? 'http://localhost:11434';
        return rtrim($host, '/') . '/api/generate';
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    protected function formatChatPayload(string $prompt, array $options = []): array
    {
        return [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'num_predict' => $options['max_tokens'] ?? 1000,
            ],
        ];
    }

    protected function extractChatResponse(array $response): string
    {
        return $response['response'] ?? '';
    }

    protected function extractTokenUsage(array $response): array
    {
        // Ollama doesn't provide detailed token usage in the same format
        // This is a simplified implementation
        $responseText = $response['response'] ?? '';
        $promptTokens = $response['prompt_eval_count'] ?? 0;
        $completionTokens = $response['eval_count'] ?? 0;
        
        return [
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $promptTokens + $completionTokens,
        ];
    }

    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        // Ollama is typically free for local usage
        return 0.0;
    }

    protected function getProviderName(): string
    {
        return 'ollama';
    }

    public function getSupportedModes(): array
    {
        return ['chat']; // Ollama currently only supports chat mode
    }

    public function stream(string $prompt, array $options = [], callable $callback = null): \Generator
    {
        $payload = $this->formatChatPayload($prompt, $options);
        $payload['stream'] = true;
        
        $headers = $this->getHeaders();
        
        $response = Http::withHeaders($headers)
            ->timeout(60)
            ->withOptions([
                'stream' => true,
            ])
            ->post($this->getApiEndpoint(), $payload);

        if (!$response->successful()) {
            throw new \Exception("Ollama streaming request failed: " . $response->body());
        }

        foreach ($response->stream() as $chunk) {
            $data = json_decode($chunk, true);
            
            if (isset($data['response'])) {
                if ($callback) {
                    $callback($data['response']);
                }
                
                yield $data['response'];
            }
            
            if (isset($data['done']) && $data['done']) {
                break;
            }
        }
    }
}
