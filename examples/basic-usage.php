<?php

/**
 * LaraFlowAI Basic Usage Examples
 * 
 * This file demonstrates how to use LaraFlowAI in your Laravel application.
 * Make sure to set up your API keys in the .env file before running these examples.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use LaraFlowAI\Facades\FlowAI;
use LaraFlowAI\Tools\HttpTool;
use LaraFlowAI\Tools\DatabaseTool;
use LaraFlowAI\Tools\FilesystemTool;

// Example 1: Basic Agent Usage
echo "=== Example 1: Basic Agent Usage ===\n";

$agent = FlowAI::agent(
    role: 'Content Writer',
    goal: 'Create engaging blog posts about Laravel and AI',
    provider: 'openai'
);

$task = FlowAI::task('Write a short blog post about the benefits of using AI in web development');

$response = $agent->handle($task);
echo "Agent Response: " . $response->getContent() . "\n\n";

// Example 2: Agent with Tools
echo "=== Example 2: Agent with Tools ===\n";

$researchAgent = FlowAI::agent(
    role: 'Research Assistant',
    goal: 'Gather and analyze information from various sources',
    provider: 'openai'
);

// Add tools to the agent
$researchAgent->addTool(new HttpTool())
    ->addTool(new DatabaseTool())
    ->addTool(new FilesystemTool());

$researchTask = FlowAI::task('Research the latest Laravel features and create a summary')
    ->setToolInput('http', [
        'url' => 'https://laravel.com/news',
        'method' => 'GET'
    ]);

$researchResponse = $researchAgent->handle($researchTask);
echo "Research Agent Response: " . $researchResponse->getContent() . "\n\n";

// Example 3: Crew Usage
echo "=== Example 3: Crew Usage ===\n";

// Create multiple agents
$writer = FlowAI::agent('Content Writer', 'Write engaging and informative content');
$editor = FlowAI::agent('Editor', 'Review and improve content quality');
$seo = FlowAI::agent('SEO Specialist', 'Optimize content for search engines');

// Create tasks
$tasks = [
    FlowAI::task('Write a comprehensive guide about Laravel 11 features'),
    FlowAI::task('Review and edit the guide for clarity and accuracy'),
    FlowAI::task('Optimize the guide for SEO with relevant keywords'),
];

// Create and execute crew
$crew = FlowAI::crew(['execution_mode' => 'sequential'])
    ->addAgent($writer)
    ->addAgent($editor)
    ->addAgent($seo)
    ->addTasks($tasks);

$crewResult = $crew->kickoff();

if ($crewResult->isSuccess()) {
    echo "Crew executed successfully!\n";
    echo "Execution time: " . $crewResult->getExecutionTime() . " seconds\n";
    echo "Successful tasks: " . $crewResult->getSuccessfulTaskCount() . "\n";
    
    foreach ($crewResult->getResponses() as $index => $response) {
        echo "\nTask " . ($index + 1) . " Response:\n";
        echo $response->getContent() . "\n";
    }
} else {
    echo "Crew execution failed: " . $crewResult->getError() . "\n";
}

// Example 4: Memory Usage
echo "\n=== Example 4: Memory Usage ===\n";

// Store information in memory
FlowAI::memory()->store('user_preferences', [
    'writing_style' => 'professional',
    'target_audience' => 'developers',
    'preferred_length' => 'medium'
]);

// Recall information
$preferences = FlowAI::memory()->recall('user_preferences');
echo "User preferences: " . json_encode($preferences) . "\n";

// Search memory
$results = FlowAI::memory()->search('Laravel');
echo "Memory search results: " . count($results) . " found\n";

// Example 5: Flow Usage
echo "\n=== Example 5: Flow Usage ===\n";

use LaraFlowAI\FlowStep;
use LaraFlowAI\FlowCondition;

$flow = FlowAI::flow(['name' => 'Content Publishing Flow']);

// Add steps to the flow
$flow->addStep(FlowStep::crew('content_creation', $crew))
    ->addStep(FlowStep::condition('quality_check', FlowCondition::simple('quality_score', '>', 8)))
    ->addStep(FlowStep::delay('publish_delay', 2))
    ->addStep(FlowStep::custom('publish', function($context) {
        echo "Publishing content...\n";
        return 'Content published successfully!';
    }));

$flowResult = $flow->run();

if ($flowResult->isSuccess()) {
    echo "Flow executed successfully!\n";
    echo "Execution time: " . $flowResult->getExecutionTime() . " seconds\n";
    echo "Steps completed: " . $flowResult->getSuccessfulStepCount() . "\n";
} else {
    echo "Flow execution failed: " . $flowResult->getError() . "\n";
}

// Example 6: Custom Provider
echo "\n=== Example 6: Custom Provider ===\n";

// Register a custom provider
FlowAI::extend('custom', function($config) {
    return new class($config) implements \LaraFlowAI\Contracts\ProviderContract {
        public function generate(string $prompt, array $options = []): string
        {
            return "Custom response to: " . substr($prompt, 0, 50) . "...";
        }
        
        public function stream(string $prompt, array $options = [], callable $callback = null): \Generator
        {
            yield "Custom streaming response";
        }
        
        public function getConfig(): array { return []; }
        public function setModel(string $model): self { return $this; }
        public function getModel(): string { return 'custom'; }
    };
});

$customAgent = FlowAI::agent('Custom Agent', 'Provide custom responses', 'custom');
$customTask = FlowAI::task('Test the custom provider');
$customResponse = $customAgent->handle($customTask);

echo "Custom Agent Response: " . $customResponse->getContent() . "\n";

echo "\n=== All Examples Completed ===\n";
