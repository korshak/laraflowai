<?php

/**
 * LaraFlowAI Multiple Providers Example
 * 
 * This file demonstrates how to use different AI providers
 * with LaraFlowAI for various use cases.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use LaraFlowAI\Facades\FlowAI;

echo "=== LaraFlowAI Multiple Providers Example ===\n\n";

// Example 1: Compare different providers
echo "1. Comparing Different Providers\n";
echo "=================================\n";

$topic = 'Laravel 11 new features';

// Create agents with different providers
$providers = [
    'openai' => ['name' => 'OpenAI GPT-4', 'model' => 'gpt-4'],
    'grok' => ['name' => 'Grok AI', 'model' => 'grok-4'],
    'gemini' => ['name' => 'Google Gemini', 'model' => 'gemini-1.5-flash'],
    'ollama' => ['name' => 'Ollama Local', 'model' => 'llama3.2:3b'],
];

$responses = [];

foreach ($providers as $provider => $info) {
    echo "Testing {$info['name']}...\n";
    
    try {
        $agent = FlowAI::agent(
            role: 'Technical Writer',
            goal: 'Write clear, accurate technical content',
            provider: $provider
        );
        
        $task = FlowAI::task("Write a brief overview of {$topic}");
        
        $startTime = microtime(true);
        $response = $agent->handle($task);
        $executionTime = microtime(true) - $startTime;
        
        $responses[$provider] = [
            'content' => $response->getContent(),
            'execution_time' => $executionTime,
            'provider' => $info['name'],
            'model' => $info['model'],
        ];
        
        echo "✅ {$info['name']} completed in " . number_format($executionTime, 2) . "s\n";
        
    } catch (Exception $e) {
        echo "❌ {$info['name']} failed: " . $e->getMessage() . "\n";
        $responses[$provider] = [
            'error' => $e->getMessage(),
            'provider' => $info['name'],
        ];
    }
    
    echo "\n";
}

// Display results
echo "Results Summary:\n";
echo "================\n";
foreach ($responses as $provider => $result) {
    if (isset($result['error'])) {
        echo "❌ {$result['provider']}: {$result['error']}\n";
    } else {
        echo "✅ {$result['provider']} ({$result['model']}): " . 
             number_format($result['execution_time'], 2) . "s\n";
        echo "   " . substr($result['content'], 0, 100) . "...\n";
    }
    echo "\n";
}

// Example 2: Provider-specific use cases
echo "2. Provider-Specific Use Cases\n";
echo "==============================\n";

// Grok for creative and humorous responses
echo "Grok - Creative and Humorous:\n";
try {
    $grokAgent = FlowAI::agent('Creative Assistant', 'Provide creative and humorous answers', 'grok');
    $grokTask = FlowAI::task('What are the main benefits of using Laravel?');
    $grokResponse = $grokAgent->handle($grokTask);
    echo "Response: " . substr($grokResponse->getContent(), 0, 150) . "...\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Gemini for Google-powered responses
echo "Gemini - Google-Powered AI:\n";
try {
    $geminiAgent = FlowAI::agent('Google Assistant', 'Provide Google-powered insights', 'gemini');
    $geminiTask = FlowAI::task('Explain the latest trends in web development');
    $geminiResponse = $geminiAgent->handle($geminiTask);
    echo "Response: " . substr($geminiResponse->getContent(), 0, 150) . "...\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 3: Fallback strategy
echo "3. Fallback Strategy\n";
echo "===================\n";

function getResponseWithFallback(string $prompt): array
{
    $providers = ['openai', 'grok', 'gemini', 'ollama'];
    
    foreach ($providers as $provider) {
        try {
            $agent = FlowAI::agent('Assistant', 'Help users', $provider);
            $task = FlowAI::task($prompt);
            $response = $agent->handle($task);
            
            return [
                'success' => true,
                'provider' => $provider,
                'content' => $response->getContent(),
                'execution_time' => $response->getExecutionTime(),
            ];
        } catch (Exception $e) {
            echo "Provider {$provider} failed: " . $e->getMessage() . "\n";
            continue;
        }
    }
    
    return [
        'success' => false,
        'error' => 'All providers failed',
    ];
}

$fallbackResult = getResponseWithFallback('What is the best way to optimize Laravel performance?');

if ($fallbackResult['success']) {
    echo "✅ Fallback successful using {$fallbackResult['provider']}\n";
    echo "Response: " . substr($fallbackResult['content'], 0, 150) . "...\n";
    echo "Execution time: " . number_format($fallbackResult['execution_time'], 2) . "s\n";
} else {
    echo "❌ All providers failed: " . $fallbackResult['error'] . "\n";
}

echo "\n";

// Example 4: Provider comparison for specific tasks
echo "4. Task-Specific Provider Comparison\n";
echo "====================================\n";

$tasks = [
    'code_generation' => 'Write a Laravel controller for user management',
    'explanation' => 'Explain how Laravel middleware works',
    'debugging' => 'Help debug a Laravel query performance issue',
    'creative' => 'Write a creative story about a developer learning Laravel',
];

foreach ($tasks as $taskType => $prompt) {
    echo "Task: {$taskType}\n";
    echo "Prompt: {$prompt}\n";
    
    $taskResults = [];
    
    foreach (['grok', 'openai', 'gemini'] as $provider) {
        try {
            $agent = FlowAI::agent('Task Specialist', 'Complete tasks effectively', $provider);
            $task = FlowAI::task($prompt);
            
            $startTime = microtime(true);
            $response = $agent->handle($task);
            $executionTime = microtime(true) - $startTime;
            
            $taskResults[$provider] = [
                'success' => true,
                'execution_time' => $executionTime,
                'content_length' => strlen($response->getContent()),
            ];
            
        } catch (Exception $e) {
            $taskResults[$provider] = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    // Show results
    foreach ($taskResults as $provider => $result) {
        if ($result['success']) {
            echo "  {$provider}: " . number_format($result['execution_time'], 2) . "s, " . 
                 $result['content_length'] . " chars\n";
        } else {
            echo "  {$provider}: Failed - " . $result['error'] . "\n";
        }
    }
    
    echo "\n";
}

// Example 5: Cost comparison
echo "5. Cost Comparison\n";
echo "==================\n";

echo "Estimated costs for 1000 tokens (approximate):\n";
echo "- OpenAI GPT-4: ~$0.03\n";
echo "- Grok AI: ~$0.01\n";
echo "- Gemini 1.5 Flash: ~$0.0005\n";
echo "- Ollama Local: Free (local processing)\n";
echo "\n";

// Example 6: Performance benchmarking
echo "6. Performance Benchmarking\n";
echo "===========================\n";

$benchmarkPrompt = 'Write a short summary of PHP 8.3 features';

$benchmarkResults = [];

foreach (['grok', 'openai', 'gemini'] as $provider) {
    echo "Benchmarking {$provider}...\n";
    
    $times = [];
    $successCount = 0;
    
    for ($i = 0; $i < 3; $i++) {
        try {
            $agent = FlowAI::agent('Benchmarker', 'Fast responses', $provider);
            $task = FlowAI::task($benchmarkPrompt);
            
            $startTime = microtime(true);
            $response = $agent->handle($task);
            $executionTime = microtime(true) - $startTime;
            
            $times[] = $executionTime;
            $successCount++;
            
        } catch (Exception $e) {
            echo "  Attempt " . ($i + 1) . " failed: " . $e->getMessage() . "\n";
        }
    }
    
    if ($successCount > 0) {
        $avgTime = array_sum($times) / count($times);
        $minTime = min($times);
        $maxTime = max($times);
        
        $benchmarkResults[$provider] = [
            'success_rate' => ($successCount / 3) * 100,
            'avg_time' => $avgTime,
            'min_time' => $minTime,
            'max_time' => $maxTime,
        ];
        
        echo "  Success rate: " . number_format($benchmarkResults[$provider]['success_rate'], 1) . "%\n";
        echo "  Average time: " . number_format($avgTime, 2) . "s\n";
        echo "  Min time: " . number_format($minTime, 2) . "s\n";
        echo "  Max time: " . number_format($maxTime, 2) . "s\n";
    } else {
        echo "  All attempts failed\n";
    }
    
    echo "\n";
}

// Show benchmark summary
echo "Benchmark Summary:\n";
foreach ($benchmarkResults as $provider => $result) {
    echo "{$provider}: {$result['success_rate']}% success, " . 
         number_format($result['avg_time'], 2) . "s avg\n";
}

echo "\n=== Multiple Providers Example Completed ===\n";
echo "LaraFlowAI supports multiple AI providers for different use cases! 🚀\n";
