<?php

/**
 * Grok Provider Usage Example
 * 
 * This example demonstrates how to use the Grok provider with LaraFlowAI
 * to interact with X's Grok AI models.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use LaraFlowAI\LaraFlowAIManager;
use LaraFlowAI\Memory\MemoryManager;

// Example 1: Basic Grok Usage
echo "=== Basic Grok Usage ===\n";

// Create a Grok provider instance
$grokProvider = new \LaraFlowAI\Providers\GrokProvider([
    'api_key' => 'your-grok-api-key-here', // Get from https://x.ai/api
    'model' => 'grok-4',
]);

// Create memory manager
$memory = new MemoryManager();

// Create an agent with Grok using FlowAI facade
$agent = FlowAI::agent(
    'Grok Assistant',
    'You are a helpful AI assistant powered by Grok. Provide insightful and unfiltered truths with a sense of humor.',
    'grok'
);

// Create a task
$task = FlowAI::task('What is the meaning of life, the universe, and everything?');

// Execute the task
try {
    $response = $agent->handle($task);
    echo "Grok Response: " . $response->getContent() . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Example 2: Using Grok with Tools
echo "=== Using Grok with Tools ===\n";

// Create an agent with Grok and tools
$agent2 = FlowAI::agent(
    'Grok Coding Assistant',
    'You are an expert coding assistant powered by Grok. Help with programming questions and provide code examples.',
    'grok'
);

// Add a tool to the agent
$agent2->addTool(new \LaraFlowAI\Tools\HttpTool());

// Create a coding task
$codingTask = FlowAI::task('Write a PHP function to calculate the Fibonacci sequence');

try {
    $response2 = $agent2->handle($codingTask);
    echo "Grok Coding Response: " . $response2->getContent() . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Example 3: Streaming with Grok
echo "=== Streaming with Grok ===\n";

try {
    $streamingTask = FlowAI::task('Tell me a short story about a robot learning to love');
    
    echo "Streaming response:\n";
    foreach ($agent->stream($streamingTask->getDescription()) as $chunk) {
        echo $chunk;
        flush(); // Force output to browser/console
    }
    echo "\n\n";
} catch (Exception $e) {
    echo "Streaming Error: " . $e->getMessage() . "\n\n";
}

// Example 4: Using Grok with Conversation History
echo "=== Conversation History with Grok ===\n";

try {
    // First message
    $firstTask = FlowAI::task('Hello, my name is Alice. What should I know about you?');
    $firstResponse = $agent->handle($firstTask);
    echo "Grok: " . $firstResponse->getContent() . "\n\n";
    
    // Follow-up message with conversation history
    $followUpTask = FlowAI::task('That sounds interesting! Can you tell me more about your capabilities?');
    
    // Add conversation history to the task
    $followUpTask->setConfig([
        'messages' => [
            ['role' => 'user', 'content' => 'Hello, my name is Alice. What should I know about you?'],
            ['role' => 'assistant', 'content' => $firstResponse->getContent()],
        ]
    ]);
    
    $followUpResponse = $agent->handle($followUpTask);
    echo "Grok: " . $followUpResponse->getContent() . "\n\n";
} catch (Exception $e) {
    echo "Conversation Error: " . $e->getMessage() . "\n\n";
}

// Example 5: Model Capabilities
echo "=== Grok Model Capabilities ===\n";

$capabilities = $grokProvider->getModelCapabilities('grok-4');
echo "Grok-4 Capabilities:\n";
foreach ($capabilities as $capability => $value) {
    if (is_bool($value)) {
        echo "- {$capability}: " . ($value ? 'Yes' : 'No') . "\n";
    } else {
        echo "- {$capability}: {$value}\n";
    }
}

echo "\nAvailable Models:\n";
$models = $grokProvider->getAvailableModels();
foreach ($models as $model => $description) {
    echo "- {$model}: {$description}\n";
}

echo "\n=== Example Complete ===\n";
echo "To use this example:\n";
echo "1. Get your Grok API key from https://x.ai/api\n";
echo "2. Replace 'your-grok-api-key-here' with your actual API key\n";
echo "3. Run: php examples/grok-usage.php\n";

