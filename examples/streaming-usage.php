<?php

/**
 * LaraFlowAI Streaming Usage Examples
 * 
 * This file demonstrates how to use LaraFlowAI streaming functionality
 * for real-time output in chat interfaces and Livewire/Vue components.
 * Make sure to set up your API keys in the .env file before running these examples.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use LaraFlowAI\Facades\FlowAI;
use LaraFlowAI\Tools\HttpTool;
use LaraFlowAI\Tools\DatabaseTool;

// Example 1: Basic Agent Streaming
echo "=== Example 1: Basic Agent Streaming ===\n";

$agent = FlowAI::agent(
    role: 'Content Writer',
    goal: 'Create engaging blog posts about Laravel and AI',
    provider: 'openai'
);

$task = FlowAI::task('Write a comprehensive guide about Laravel 12 features');

// Stream the response
$streamingResponse = $agent->stream($task);

echo "Streaming response:\n";
while ($streamingResponse->hasMoreChunks()) {
    $chunk = $streamingResponse->getNextChunk();
    if ($chunk !== null) {
        echo $chunk;
        // In a real application, you would send this to the frontend
        // via WebSocket, Server-Sent Events, or similar
    }
}

echo "\n\nComplete response:\n";
echo $streamingResponse->getContent() . "\n";
echo "Execution time: " . $streamingResponse->getExecutionTime() . "s\n\n";

// Example 2: Agent Streaming with Callback
echo "=== Example 2: Agent Streaming with Callback ===\n";

$callbackAgent = FlowAI::agent(
    role: 'Code Reviewer',
    goal: 'Review and improve code quality',
    provider: 'openai'
);

$callbackTask = FlowAI::task('Review this PHP code for best practices');

// Define a callback to handle each chunk
$chunkCallback = function($chunk, $fullContent) {
    echo "Chunk received: " . strlen($chunk) . " characters\n";
    echo "Total content so far: " . strlen($fullContent) . " characters\n";
    echo "Chunk: " . $chunk . "\n---\n";
};

$streamingResponse2 = $callbackAgent->stream($callbackTask, $chunkCallback);

// Consume all chunks
while ($streamingResponse2->hasMoreChunks()) {
    $streamingResponse2->getNextChunk();
}

echo "Final content length: " . $streamingResponse2->getContentLength() . " characters\n\n";

// Example 3: Crew Streaming
echo "=== Example 3: Crew Streaming ===\n";

// Create multiple agents
$writer = FlowAI::agent('Content Writer', 'Write engaging content', 'openai');
$editor = FlowAI::agent('Editor', 'Review and improve content', 'openai');

// Create tasks
$tasks = [
    FlowAI::task('Write a detailed article about AI in web development'),
    FlowAI::task('Review and improve the article from the previous task'),
];

// Create crew
$crew = FlowAI::crew()
    ->addAgent($writer)
    ->addAgent($editor)
    ->addTasks($tasks);

// Stream crew execution
echo "Streaming crew execution:\n";
foreach ($crew->stream() as $result) {
    if ($result['is_streaming'] && !$result['is_complete']) {
        // This is a streaming chunk
        echo "Streaming chunk from task " . $result['task_index'] . ": " . $result['chunk'] . "\n";
    } elseif ($result['is_complete']) {
        // This is a completed task
        echo "Task " . $result['task_index'] . " completed by " . $result['agent'] . "\n";
        echo "Response length: " . strlen($result['response']->getContent()) . " characters\n";
        echo "Execution time: " . $result['response']->getExecutionTime() . "s\n";
        echo "---\n";
    }
}

// Example 4: Task with Streaming Configuration
echo "=== Example 4: Task with Streaming Configuration ===\n";

$streamingTask = FlowAI::task('Explain the benefits of using AI in Laravel applications')
    ->stream(function($chunk, $fullContent) {
        // This callback will be called for each chunk
        echo "Received chunk: " . $chunk . "\n";
    });

$streamingAgent = FlowAI::agent('AI Expert', 'Explain AI concepts clearly', 'openai');

// The task is already configured for streaming
$response = $streamingAgent->stream($streamingTask);

// Consume the response
while ($response->hasMoreChunks()) {
    $response->getNextChunk();
}

echo "Task streaming completed!\n";
echo "Final content: " . $response->getContent() . "\n\n";

// Example 5: Streaming with Tools
echo "=== Example 5: Streaming with Tools ===\n";

$researchAgent = FlowAI::agent(
    role: 'Research Assistant',
    goal: 'Gather and analyze information from various sources',
    provider: 'openai'
);

// Add tools
$researchAgent->addTool(new HttpTool())
    ->addTool(new DatabaseTool());

$researchTask = FlowAI::task('Research the latest Laravel features and create a summary')
    ->setToolInput('http', [
        'url' => 'https://laravel.com/news',
        'method' => 'GET'
    ]);

$streamingResponse3 = $researchAgent->stream($researchTask);

echo "Research streaming response:\n";
while ($streamingResponse3->hasMoreChunks()) {
    $chunk = $streamingResponse3->getNextChunk();
    if ($chunk !== null) {
        echo $chunk;
    }
}

echo "\n\nTool results:\n";
$toolResults = $streamingResponse3->getToolResults();
foreach ($toolResults as $tool => $result) {
    echo "Tool {$tool}: " . (is_string($result) ? substr($result, 0, 100) . '...' : gettype($result)) . "\n";
}

// Example 6: Streaming Response Statistics
echo "=== Example 6: Streaming Response Statistics ===\n";

$statsAgent = FlowAI::agent('Analyst', 'Provide detailed analysis', 'openai');
$statsTask = FlowAI::task('Analyze the performance of Laravel applications');

$statsResponse = $statsAgent->stream($statsTask);

// Consume all chunks
while ($statsResponse->hasMoreChunks()) {
    $statsResponse->getNextChunk();
}

$stats = $statsResponse->getStats();
echo "Response Statistics:\n";
foreach ($stats as $key => $value) {
    echo "{$key}: {$value}\n";
}

// Example 7: Converting Streaming Response to Regular Response
echo "=== Example 7: Converting to Regular Response ===\n";

$convertAgent = FlowAI::agent('Converter', 'Convert streaming to regular', 'openai');
$convertTask = FlowAI::task('Explain the difference between streaming and regular responses');

$streamingResponse4 = $convertAgent->stream($convertTask);

// Consume all chunks
while ($streamingResponse4->hasMoreChunks()) {
    $streamingResponse4->getNextChunk();
}

// Convert to regular response
$regularResponse = $streamingResponse4->toResponse();

echo "Converted to regular response:\n";
echo "Content: " . $regularResponse->getContent() . "\n";
echo "Agent Role: " . $regularResponse->getAgentRole() . "\n";
echo "Execution Time: " . $regularResponse->getExecutionTime() . "s\n";

// Example 8: Buffer Management
echo "=== Example 8: Buffer Management ===\n";

$bufferAgent = FlowAI::agent('Buffer Manager', 'Demonstrate buffer management', 'openai');
$bufferTask = FlowAI::task('Write a long article about buffer management in streaming');

$bufferResponse = $bufferAgent->stream($bufferTask);

// Set a smaller buffer size for demonstration
$bufferResponse->setBufferSize(5);

echo "Buffer size set to: " . $bufferResponse->getBufferSize() . "\n";

$chunkCount = 0;
while ($bufferResponse->hasMoreChunks()) {
    $chunk = $bufferResponse->getNextChunk();
    if ($chunk !== null) {
        $chunkCount++;
        echo "Chunk {$chunkCount}: " . $chunk . "\n";
        echo "Current buffer: " . $bufferResponse->getBuffer() . "\n";
    }
}

echo "Total chunks received: {$chunkCount}\n";
echo "Final content length: " . $bufferResponse->getContentLength() . " characters\n";

echo "\n=== All Streaming Examples Completed ===\n";
