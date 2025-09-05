<?php

namespace LaraFlowAI\Tools;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use LaraFlowAI\MCP\MCPClient;

class MCPTool extends BaseTool
{
    protected MCPClient $mcpClient;
    protected array $serverConfig;

    public function __construct(array $config = [])
    {
        $this->serverConfig = $config;
        $this->mcpClient = app(MCPClient::class);
        
        parent::__construct(
            'mcp',
            'Interact with external MCP (Model Context Protocol) servers',
            array_merge([
                'input_schema' => [
                    'server' => ['required' => true, 'type' => 'string'],
                    'action' => ['required' => true, 'type' => 'string'],
                    'parameters' => ['required' => false, 'type' => 'array', 'default' => []],
                    'timeout' => ['required' => false, 'type' => 'integer', 'default' => 30],
                ]
            ], $config)
        );
    }


    /**
     * Execute MCP tool
     */
    public function run(array $input): mixed
    {
        if (!$this->validateInput($input)) {
            throw new \InvalidArgumentException('Invalid input for MCP tool');
        }

        $server = $input['server'];
        $action = $input['action'];
        $parameters = $input['parameters'] ?? [];

        try {
            // Check if server is configured
            if (!$this->mcpClient->hasServer($server)) {
                throw new \InvalidArgumentException("MCP server '{$server}' is not configured or not enabled");
            }

            // Execute action using MCP client
            $result = $this->mcpClient->execute($server, $action, $parameters);
            
            $this->logExecution($input, $result);
            return $result;

        } catch (\Exception $e) {
            $this->logExecution($input, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get available tools from all servers
     */
    public function getAvailableTools(): array
    {
        return $this->mcpClient->getAllTools();
    }

    /**
     * Get available tools from specific server
     */
    public function getServerTools(string $serverId): array
    {
        return $this->mcpClient->getServerTools($serverId);
    }

    /**
     * Refresh tools cache for all servers
     */
    public function refreshTools(): void
    {
        $this->mcpClient->clearCaches();
    }

    /**
     * Test connection to all servers
     */
    public function testConnections(): array
    {
        $results = [];
        $servers = $this->mcpClient->getServers();
        
        foreach (array_keys($servers) as $serverId) {
            $results[$serverId] = $this->mcpClient->testConnection($serverId);
        }
        
        return $results;
    }

    /**
     * Test connection to specific server
     */
    public function testConnection(string $serverId): bool
    {
        return $this->mcpClient->testConnection($serverId);
    }

    /**
     * Get health status of all servers
     */
    public function getHealthStatus(): array
    {
        return $this->mcpClient->getHealthStatus();
    }

    /**
     * Get health status of specific server
     */
    public function getServerHealth(string $serverId): array
    {
        return $this->mcpClient->getServerHealth($serverId);
    }

    /**
     * Get server configuration
     */
    public function getServerConfig(): array
    {
        return $this->serverConfig;
    }

    /**
     * Set server configuration
     */
    public function setServerConfig(array $config): self
    {
        $this->serverConfig = array_merge($this->serverConfig, $config);
        return $this;
    }

    /**
     * Get MCP client instance
     */
    public function getMCPClient(): MCPClient
    {
        return $this->mcpClient;
    }
}
