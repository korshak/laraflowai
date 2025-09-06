<?php

/**
 * LaraFlowAI MCP (Model Context Protocol) Usage Examples
 * 
 * This file demonstrates how to use MCP servers with LaraFlowAI
 * to extend functionality with external tools and services.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use LaraFlowAI\Facades\FlowAI;
use LaraFlowAI\Tools\MCPTool;
use LaraFlowAI\MCP\MCPClient;

// Example 1: Basic MCP Tool Usage
echo "=== Example 1: Basic MCP Tool Usage ===\n";

// Create an agent with MCP tool
$agent = FlowAI::agent(
    role: 'Research Assistant',
    goal: 'Gather information using external MCP servers'
);

// Add MCP tool to the agent
$mcpTool = new MCPTool();
$agent->addTool($mcpTool);

// Create a task that uses MCP server
$task = FlowAI::task('Search for information about Laravel 11 features')
    ->setToolInput('mcp', [
        'server' => 'example_server',
        'action' => 'search_web',
        'parameters' => [
            'query' => 'Laravel 11 new features',
            'limit' => 5
        ]
    ]);

// Handle the task
try {
    $response = $agent->handle($task);
    echo "Response: " . $response->getContent() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 2: Direct MCP Client Usage
echo "=== Example 2: Direct MCP Client Usage ===\n";

$mcpClient = app(MCPClient::class);

// Test connection to a server
if ($mcpClient->testConnection('example_server')) {
    echo "✅ Connection to example_server successful\n";
} else {
    echo "❌ Connection to example_server failed\n";
}

// Get available tools from server
$tools = $mcpClient->getServerTools('example_server');
echo "Available tools: " . count($tools) . "\n";
foreach ($tools as $tool) {
    echo "  - " . $tool['name'] . ": " . $tool['description'] . "\n";
}

// Get server health status
$health = $mcpClient->getServerHealth('example_server');
echo "Server health: " . $health['status'] . "\n";
if ($health['uptime']) {
    echo "Uptime: " . $health['uptime'] . "\n";
}

echo "\n";

// Example 3: Multiple MCP Servers
echo "=== Example 3: Multiple MCP Servers ===\n";

// Get all configured servers
$servers = $mcpClient->getServers();
echo "Configured servers: " . count($servers) . "\n";

foreach ($servers as $serverId => $server) {
    echo "Server: {$serverId}\n";
    echo "  Name: " . $server['name'] . "\n";
    echo "  URL: " . $server['url'] . "\n";
    echo "  Enabled: " . ($server['enabled'] ? 'Yes' : 'No') . "\n";
    echo "  Available actions: " . implode(', ', $server['available_actions'] ?? []) . "\n";
}

echo "\n";

// Example 4: Error Handling
echo "=== Example 4: Error Handling ===\n";

try {
    // Try to use a non-existent server
    $result = $mcpClient->execute('non_existent_server', 'test_action');
} catch (InvalidArgumentException $e) {
    echo "Caught expected error: " . $e->getMessage() . "\n";
}

try {
    // Try to execute with invalid parameters
    $mcpTool = new MCPTool();
    $mcpTool->run([
        'server' => 'example_server',
        'action' => 'test_action'
        // Missing required parameters
    ]);
} catch (InvalidArgumentException $e) {
    echo "Caught validation error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 5: Advanced MCP Usage with Crew
echo "=== Example 5: Advanced MCP Usage with Crew ===\n";

// Create specialized agents with different MCP tools
$researcher = FlowAI::agent(
    role: 'Web Researcher',
    goal: 'Research information from web sources',
    provider: 'openai'
)->addTool(new MCPTool());

$analyzer = FlowAI::agent(
    role: 'Data Analyzer',
    goal: 'Analyze and process data',
    provider: 'groq'
)->addTool(new MCPTool());

// Create tasks that use different MCP servers
$researchTask = FlowAI::task('Research the latest AI trends')
    ->setToolInput('mcp', [
        'server' => 'example_server',
        'action' => 'search_web',
        'parameters' => [
            'query' => 'AI trends 2024',
            'sources' => ['news', 'blogs', 'academic']
        ]
    ]);

$analysisTask = FlowAI::task('Analyze the research data')
    ->setToolInput('mcp', [
        'server' => 'analytics_server',
        'action' => 'analyze_data',
        'parameters' => [
            'data_type' => 'research_results',
            'analysis_type' => 'trend_analysis'
        ]
    ]);

// Create a crew to execute both tasks
$crew = FlowAI::crew()
->agents([$researcher, $analyzer])
->tasks([$researchTask, $analysisTask]);

// Execute the crew
try {
    $result = $crew->execute();
    
    if ($result->isSuccess()) {
        echo "✅ Crew execution successful\n";
        echo "Execution time: " . $result->getExecutionTime() . "s\n";
        echo "Successful tasks: " . $result->getSuccessfulTaskCount() . "\n";
        
        foreach ($result->getResults() as $index => $taskResult) {
            echo "Task " . ($index + 1) . " result:\n";
            echo $taskResult['response']->getContent() . "\n\n";
        }
    } else {
        echo "❌ Crew execution failed: " . $result->getErrorMessage() . "\n";
    }
} catch (Exception $e) {
    echo "Error executing crew: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 6: MCP Server Management
echo "=== Example 6: MCP Server Management ===\n";

// Get server statistics
$stats = $mcpClient->getAllServerStats();
foreach ($stats as $serverId => $serverStats) {
    echo "Server: {$serverId}\n";
    echo "  Status: " . $serverStats['health_status'] . "\n";
    echo "  Tools: " . $serverStats['tools_count'] . "\n";
    echo "  Last check: " . $serverStats['last_health_check'] . "\n";
}

// Refresh tools cache
$mcpClient->clearCaches();
echo "✅ MCP caches cleared\n";

echo "\n";

// Example 7: Configuration Examples
echo "=== Example 7: Configuration Examples ===\n";

echo "To configure MCP servers, add to your .env file:\n";
echo "LARAFLOWAI_MCP_ENABLED=true\n";
echo "MCP_EXAMPLE_SERVER_URL=http://localhost:3000/api/mcp\n";
echo "MCP_EXAMPLE_SERVER_TOKEN=your-auth-token\n";
echo "MCP_EXAMPLE_SERVER_ENABLED=true\n";

echo "\nOr configure in config/laraflowai.php:\n";
echo "```php\n";
echo "'mcp' => [\n";
echo "    'enabled' => true,\n";
echo "    'servers' => [\n";
echo "        'my_server' => [\n";
echo "            'name' => 'My MCP Server',\n";
echo "            'url' => 'http://my-server:3000/api/mcp',\n";
echo "            'auth_token' => 'my-token',\n";
echo "            'enabled' => true,\n";
echo "            'available_actions' => ['search', 'analyze', 'process']\n";
echo "        ]\n";
echo "    ]\n";
echo "]\n";
echo "```\n";

echo "\n=== MCP Usage Examples Complete ===\n";
