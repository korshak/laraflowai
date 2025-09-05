<?php

namespace LaraFlowAI\Providers;

class AnthropicProvider extends BaseProvider
{
    protected function getDefaultModel(): string
    {
        return 'claude-3-sonnet-20240229';
    }

    protected function getApiEndpoint(): string
    {
        return 'https://api.anthropic.com/v1/messages';
    }

    protected function getHeaders(): array
    {
        return [
            'x-api-key' => $this->config['api_key'],
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ];
    }

    protected function formatChatPayload(string $prompt, array $options = []): array
    {
        return [
            'model' => $this->model,
            'max_tokens' => $options['max_tokens'] ?? 1000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
        ];
    }

    protected function extractChatResponse(array $response): string
    {
        return $response['content'][0]['text'] ?? '';
    }

    protected function extractTokenUsage(array $response): array
    {
        $usage = $response['usage'] ?? [];
        return [
            'prompt_tokens' => $usage['input_tokens'] ?? 0,
            'completion_tokens' => $usage['output_tokens'] ?? 0,
            'total_tokens' => ($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0),
        ];
    }

    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        // Anthropic pricing (Claude 3 & 4) - update as needed
        $pricing = [
            // Claude 3
            'claude-3-opus' => ['prompt' => 15.00 / 1000000, 'completion' => 75.00 / 1000000],
            'claude-3-sonnet' => ['prompt' => 3.00 / 1000000, 'completion' => 15.00 / 1000000],
            'claude-3-haiku' => ['prompt' => 0.80 / 1000000, 'completion' => 4.00 / 1000000],
            'claude-3-flash' => ['prompt' => 0.25 / 1000000, 'completion' => 1.25 / 1000000],
        
            // Claude 4
            'claude-4-opus' => ['prompt' => 15.00 / 1000000, 'completion' => 75.00 / 1000000],
            'claude-4-sonnet' => ['prompt' => 3.00 / 1000000, 'completion' => 15.00 / 1000000],
            'claude-4-sonnet-long-context' => ['prompt' => 6.00 / 1000000, 'completion' => 22.50 / 1000000],
        ];

        $modelPricing = $pricing[$this->model] ?? $pricing['claude-3-sonnet-20240229'];
        
        return ($promptTokens / 1000 * $modelPricing['prompt']) + 
               ($completionTokens / 1000 * $modelPricing['completion']);
    }

    protected function getProviderName(): string
    {
        return 'anthropic';
    }

    public function getSupportedModes(): array
    {
        return ['chat']; // Anthropic currently only supports chat mode
    }
}
