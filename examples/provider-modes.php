<?php

require __DIR__.'/../vendor/autoload.php';

use LaraFlowAI\Facades\FlowAI;

// Example: Using different provider modes

echo "=== LaraFlowAI Provider Modes Example ===\n\n";

// 1. Chat Mode (Default)
echo "1. Chat Mode (Default):\n";
$chatAgent = FlowAI::agent(
    'Assistant',
    'Help users with questions',
    'openai'
);

$chatTask = FlowAI::task('What is the capital of France?');
$chatResult = $chatAgent->handle($chatTask);
echo "Chat Result: " . $chatResult->getContent() . "\n\n";

// 2. Completion Mode (Text Completion)
echo "2. Completion Mode (Text Completion):\n";
$completionAgent = FlowAI::agent(
    'Text Generator',
    'Generate text completions',
    'openai'
);

$completionTask = FlowAI::task('The future of artificial intelligence is');
$completionResult = $completionAgent->handle($completionTask);
echo "Completion Result: " . $completionResult->getContent() . "\n\n";

// 3. Embedding Mode (Vector Embeddings)
echo "3. Embedding Mode (Vector Embeddings):\n";
$embeddingAgent = FlowAI::agent(
    'Text Analyzer',
    'Generate text embeddings',
    'openai'
);

$embeddingTask = FlowAI::task('This is a sample text for embedding');
$embeddingResult = $embeddingAgent->handle($embeddingTask);
echo "Embedding Result: " . $embeddingResult->getContent() . "\n\n";

// 4. Check Provider Mode Support
echo "4. Provider Mode Support:\n";
try {
    $openaiProvider = FlowAI::llm('openai');
    echo "OpenAI supported modes: " . implode(', ', $openaiProvider->getSupportedModes()) . "\n";
} catch (Exception $e) {
    echo "OpenAI provider not available: " . $e->getMessage() . "\n";
}

try {
    $grokProvider = FlowAI::llm('grok');
    echo "Grok supported modes: " . implode(', ', $grokProvider->getSupportedModes()) . "\n";
} catch (Exception $e) {
    echo "Grok provider not available: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Dynamic Mode Switching
echo "5. Dynamic Mode Switching:\n";
$dynamicAgent = FlowAI::agent(
    'Multi-Mode Assistant',
    'Switch between different modes',
    'openai'
);

// Start in chat mode
$chatTask = FlowAI::task('Hello, how are you?');
$chatResponse = $dynamicAgent->handle($chatTask);
echo "Chat mode response: " . $chatResponse->getContent() . "\n";

// Switch to completion mode
$completionTask = FlowAI::task('The weather today is');
$completionResponse = $dynamicAgent->handle($completionTask);
echo "Completion mode response: " . $completionResponse->getContent() . "\n";

// Switch to embedding mode
$embeddingTask = FlowAI::task('Sample text for embedding');
$embeddingResponse = $dynamicAgent->handle($embeddingTask);
echo "Embedding mode response: " . $embeddingResponse->getContent() . "\n\n";

echo "=== Example Complete ===\n";
