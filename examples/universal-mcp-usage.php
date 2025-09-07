<?php

require_once __DIR__ . '/../vendor/autoload.php';

use LaraFlowAI\MCP\MCPClient;
use LaraFlowAI\MCP\Constants\MCPMethods;

// MCP servers configuration
$config = [
    'servers' => [
        'claude-mcp' => [
            'name' => 'Claude MCP Server',
            'url' => 'https://api.anthropic.com/mcp',
            'enabled' => true,
            'timeout' => 30,
            'auth_token' => 'your-anthropic-api-key',
            'auth_type' => 'bearer',
            'headers' => [
                'X-API-Version' => '2024-11-05'
            ]
        ],
        'openai-mcp' => [
            'name' => 'OpenAI MCP Server',
            'url' => 'https://api.openai.com/mcp',
            'enabled' => true,
            'timeout' => 30,
            'auth_token' => 'your-openai-api-key',
            'auth_type' => 'bearer'
        ],
        'local-mcp' => [
            'name' => 'Local MCP Server',
            'url' => 'http://localhost:3000/mcp',
            'enabled' => true,
            'timeout' => 10,
            'auth_type' => 'none'
        ]
    ],
    'retry_attempts' => 3,
    'retry_delay' => 1000,
    'cache_tools_ttl' => 3600,
    'cache_resources_ttl' => 1800,
    'logging' => [
        'enabled' => true,
        'log_requests' => true,
        'log_responses' => false
    ]
];

try {
    // Create MCP client
    $mcpClient = new MCPClient($config);

    echo "=== Universal MCP Client Demo ===\n\n";

    // 1. Initialize servers
    echo "1. Initializing servers...\n";
    foreach (array_keys($mcpClient->getServers()) as $serverId) {
        try {
            $response = $mcpClient->initialize($serverId);
            if ($response->isSuccess()) {
                echo "✅ {$serverId}: Initialized successfully\n";
            } else {
                echo "❌ {$serverId}: Failed to initialize - {$response->getErrorMessage()}\n";
            }
        } catch (Exception $e) {
            echo "❌ {$serverId}: Error - {$e->getMessage()}\n";
        }
    }

    echo "\n";

    // 2. Test connections
    echo "2. Testing connections...\n";
    foreach (array_keys($mcpClient->getServers()) as $serverId) {
        $isConnected = $mcpClient->testConnection($serverId);
        echo ($isConnected ? "✅" : "❌") . " {$serverId}: " . ($isConnected ? "Connected" : "Failed") . "\n";
    }

    echo "\n";

    // 3. Get available tools
    echo "3. Getting available tools...\n";
    foreach (array_keys($mcpClient->getServers()) as $serverId) {
        try {
            $tools = $mcpClient->getTools($serverId);
            echo "🔧 {$serverId}: " . count($tools) . " tools available\n";
            
            // Show first 3 tools
            foreach (array_slice($tools, 0, 3) as $tool) {
                echo "   - {$tool->name}: {$tool->description}\n";
            }
            if (count($tools) > 3) {
                echo "   ... and " . (count($tools) - 3) . " more\n";
            }
        } catch (Exception $e) {
            echo "❌ {$serverId}: Error getting tools - {$e->getMessage()}\n";
        }
    }

    echo "\n";

    // 4. Get available resources
    echo "4. Getting available resources...\n";
    foreach (array_keys($mcpClient->getServers()) as $serverId) {
        try {
            $resources = $mcpClient->getResources($serverId);
            echo "📁 {$serverId}: " . count($resources) . " resources available\n";
            
            // Show first 3 resources
            foreach (array_slice($resources, 0, 3) as $resource) {
                echo "   - {$resource->name} ({$resource->uri})\n";
            }
            if (count($resources) > 3) {
                echo "   ... and " . (count($resources) - 3) . " more\n";
            }
        } catch (Exception $e) {
            echo "❌ {$serverId}: Error getting resources - {$e->getMessage()}\n";
        }
    }

    echo "\n";

    // 5. Example tool call
    echo "5. Example tool call...\n";
    $serverId = 'claude-mcp';
    if ($mcpClient->hasServer($serverId)) {
        try {
            // Get list of tools
            $tools = $mcpClient->getTools($serverId);
            if (!empty($tools)) {
                $tool = $tools[0];
                echo "🔧 Calling tool: {$tool->name}\n";
                
                $response = $mcpClient->callTool($serverId, $tool->name, []);
                if ($response->isSuccess()) {
                    echo "✅ Tool call successful\n";
                    echo "Result: " . json_encode($response->getResult(), JSON_PRETTY_PRINT) . "\n";
                } else {
                    echo "❌ Tool call failed: {$response->getErrorMessage()}\n";
                }
            } else {
                echo "ℹ️  No tools available for {$serverId}\n";
            }
        } catch (Exception $e) {
            echo "❌ Error calling tool: {$e->getMessage()}\n";
        }
    } else {
        echo "ℹ️  Server {$serverId} not available\n";
    }

    echo "\n";

    // 6. Server statistics
    echo "6. Server statistics...\n";
    $stats = $mcpClient->getAllServerStats();
    foreach ($stats as $serverId => $stat) {
        echo "📊 {$serverId}:\n";
        echo "   Name: {$stat['name']}\n";
        echo "   Status: {$stat['health_status']}\n";
        echo "   Tools: {$stat['tools_count']}\n";
        echo "   Resources: {$stat['resources_count']}\n";
        echo "   Prompts: {$stat['prompts_count']}\n";
        echo "   Samples: {$stat['samples_count']}\n";
        echo "   Last Check: {$stat['last_health_check']}\n";
        echo "\n";
    }

    // 7. Server capabilities check
    echo "7. Server capabilities...\n";
    foreach (array_keys($mcpClient->getServers()) as $serverId) {
        $capabilities = $mcpClient->getServerCapabilities($serverId);
        echo "🔍 {$serverId} capabilities:\n";
        foreach ($capabilities as $capability => $value) {
            echo "   - {$capability}: " . (is_bool($value) ? ($value ? 'true' : 'false') : json_encode($value)) . "\n";
        }
        echo "\n";
    }

    echo "=== Demo completed ===\n";

} catch (Exception $e) {
    echo "❌ Fatal error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
