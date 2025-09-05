<?php

namespace LaraFlowAI\Providers;

class GeminiProvider extends BaseProvider
{
    protected function getDefaultModel(): string
    {
        return 'gemini-pro';
    }

    protected function getApiEndpoint(): string
    {
        $model = $this->model;
        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
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
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $options['max_tokens'] ?? 1000,
                'temperature' => $options['temperature'] ?? 0.7,
                'topP' => $options['top_p'] ?? 0.8,
                'topK' => $options['top_k'] ?? 40,
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ];
    }

    protected function extractChatResponse(array $response): string
    {
        return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    protected function extractTokenUsage(array $response): array
    {
        $usageMetadata = $response['usageMetadata'] ?? [];
        return [
            'prompt_tokens' => $usageMetadata['promptTokenCount'] ?? 0,
            'completion_tokens' => $usageMetadata['candidatesTokenCount'] ?? 0,
            'total_tokens' => $usageMetadata['totalTokenCount'] ?? 0,
        ];
    }

    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        // Gemini pricing (as of 2025) - update as needed
        $pricing = [
            'gemini-2.5-pro' => ['prompt' => 1.25 / 1000000, 'completion' => 10.00 / 1000000], // per million tokens
            'gemini-2.5-flash' => ['prompt' => 0.075 / 1000000, 'completion' => 0.30 / 1000000], // per million tokens
            'gemini-2.5-flash-lite' => ['prompt' => 0.019 / 1000000, 'completion' => 0.075 / 1000000], // per million tokens
        ];

        $modelPricing = $pricing[$this->model] ?? $pricing['gemini-pro'];
        
        return ($promptTokens / 1000 * $modelPricing['prompt']) + 
               ($completionTokens / 1000 * $modelPricing['completion']);
    }

    protected function getProviderName(): string
    {
        return 'gemini';
    }

    public function getSupportedModes(): array
    {
        return ['chat']; // Gemini currently only supports chat mode
    }

    public function generate(string $prompt, array $options = []): string
    {
        try {
            $payload = $this->formatPayload($prompt, $options);
            $headers = $this->getHeaders();
            
            // Add API key as query parameter for Gemini
            $apiKey = $this->config['api_key'];
            $endpoint = $this->getApiEndpoint() . "?key={$apiKey}";
            
            \Illuminate\Support\Facades\Log::info('LaraFlowAI: Sending request to Gemini', [
                'provider' => static::class,
                'model' => $this->model,
                'prompt_length' => strlen($prompt)
            ]);

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->timeout(60)
                ->post($endpoint, $payload);

            if (!$response->successful()) {
                throw new \Exception("Gemini request failed: " . $response->body());
            }

            $responseData = $response->json();
            $result = $this->extractResponse($responseData);
            
            // Track token usage
            $tokenUsage = $this->extractTokenUsage($responseData);
            if (!empty($tokenUsage)) {
                $cost = $this->calculateCost(
                    $tokenUsage['prompt_tokens'] ?? 0,
                    $tokenUsage['completion_tokens'] ?? 0
                );
                
                $this->tokenTracker->track(
                    $this->getProviderName(),
                    $this->model,
                    $tokenUsage['prompt_tokens'] ?? 0,
                    $tokenUsage['completion_tokens'] ?? 0,
                    $cost,
                    ['prompt_length' => strlen($prompt)]
                );
            }
            
            \Illuminate\Support\Facades\Log::info('LaraFlowAI: Received response from Gemini', [
                'provider' => static::class,
                'response_length' => strlen($result),
                'token_usage' => $tokenUsage ?? null
            ]);

            return $result;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('LaraFlowAI: Gemini request failed', [
                'provider' => static::class,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    public function stream(string $prompt, array $options = [], callable $callback = null): \Generator
    {
        // Gemini doesn't support streaming in the same way as OpenAI
        // For now, we'll just return the full response
        $response = $this->generate($prompt, $options);
        
        if ($callback) {
            $callback($response);
        }
        
        yield $response;
    }
}
