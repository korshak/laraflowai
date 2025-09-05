<?php

require __DIR__.'/../vendor/autoload.php';

use LaraFlowAI\Facades\FlowAI;

// Example: Using different provider modes

echo "=== LaraFlowAI Provider Modes Example ===\n\n";

// 1. Chat Mode (Default)
echo "1. Chat Mode (Default):\n";
$chatAgent = FlowAI::agent([
    'role' => 'Assistant',
    'goal' => 'Help users with questions',
    'provider' => 'openai',
    'config' => [
        'model' => 'gpt-4',
        'mode' => 'chat' // This is the default
    ]
]);

$chatResult = $chatAgent->execute('What is the capital of France?');
echo "Chat Result: " . $chatResult . "\n\n";

// 2. Completion Mode (Text Completion)
echo "2. Completion Mode (Text Completion):\n";
$completionAgent = FlowAI::agent([
    'role' => 'Text Generator',
    'goal' => 'Generate text completions',
    'provider' => 'openai',
    'config' => [
        'model' => 'gpt-4',
        'mode' => 'completion'
    ]
]);

$completionResult = $completionAgent->execute('The future of artificial intelligence is');
echo "Completion Result: " . $completionResult . "\n\n";

// 3. Embedding Mode (Vector Embeddings)
echo "3. Embedding Mode (Vector Embeddings):\n";
$embeddingAgent = FlowAI::agent([
    'role' => 'Text Analyzer',
    'goal' => 'Generate text embeddings',
    'provider' => 'openai',
    'config' => [
        'model' => 'text-embedding-ada-002',
        'mode' => 'embedding'
    ]
]);

$embeddingResult = $embeddingAgent->execute('This is a sample text for embedding');
echo "Embedding Result: " . json_encode($embeddingResult) . "\n\n";

// 4. Check Provider Mode Support
echo "4. Provider Mode Support:\n";
$openaiProvider = app('laraflowai.manager')->getProvider('openai');
$deepseekProvider = app('laraflowai.manager')->getProvider('deepseek');

echo "OpenAI supported modes: " . implode(', ', $openaiProvider->getSupportedModes()) . "\n";
echo "DeepSeek supported modes: " . implode(', ', $deepseekProvider->getSupportedModes()) . "\n\n";

// 5. Dynamic Mode Switching
echo "5. Dynamic Mode Switching:\n";
$dynamicAgent = FlowAI::agent([
    'role' => 'Multi-Mode Assistant',
    'goal' => 'Switch between different modes',
    'provider' => 'openai'
]);

// Start in chat mode
$dynamicAgent->setMode('chat');
$chatResponse = $dynamicAgent->execute('Hello, how are you?');
echo "Chat mode response: " . $chatResponse . "\n";

// Switch to completion mode
$dynamicAgent->setMode('completion');
$completionResponse = $dynamicAgent->execute('The weather today is');
echo "Completion mode response: " . $completionResponse . "\n";

// Switch to embedding mode
$dynamicAgent->setMode('embedding');
$embeddingResponse = $dynamicAgent->execute('Sample text for embedding');
echo "Embedding mode response: " . json_encode($embeddingResponse) . "\n\n";

echo "=== Example Complete ===\n";
