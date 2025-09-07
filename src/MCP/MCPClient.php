<?php

declare(strict_types=1);

namespace LaraFlowAI\MCP;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use LaraFlowAI\MCP\Structures\MCPRequest;
use LaraFlowAI\MCP\Structures\MCPResponse;
use LaraFlowAI\MCP\Structures\MCPServerConfig;
use LaraFlowAI\MCP\Structures\MCPTool;
use LaraFlowAI\MCP\Structures\MCPResource;
use LaraFlowAI\MCP\Constants\MCPMethods;
use LaraFlowAI\MCP\Constants\MCPErrorCodes;
use LaraFlowAI\MCP\Exceptions\MCPException;
use LaraFlowAI\MCP\Exceptions\MCPServerNotFoundException;
use LaraFlowAI\MCP\Exceptions\MCPConnectionException;
use LaraFlowAI\MCP\Exceptions\MCPExecutionException;

class MCPClient
{
    protected array $config;
    protected array $servers = [];
    protected array $capabilities = [];
    protected int $requestId = 0;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->initializeServers();
    }

    /**
     * Initialize MCP servers from configuration
     */
    protected function initializeServers(): void
    {
        $serversConfig = $this->config['servers'] ?? [];
        
        foreach ($serversConfig as $serverId => $serverConfig) {
            if ($serverConfig['enabled'] ?? true) {
                $this->servers[$serverId] = MCPServerConfig::fromArray($serverId, $serverConfig);
            }
        }
    }

    /**
     * Get all configured servers
     */
    public function getServers(): array
    {
        return $this->servers;
    }

    /**
     * Get a specific server configuration
     */
    public function getServer(string $serverId): ?MCPServerConfig
    {
        return $this->servers[$serverId] ?? null;
    }

    /**
     * Check if server exists and is enabled
     */
    public function hasServer(string $serverId): bool
    {
        return isset($this->servers[$serverId]);
    }

    /**
     * Execute MCP method on server
     */
    public function execute(string $serverId, string $method, array $params = []): MCPResponse
    {
        $server = $this->getServer($serverId);
        
        if (!$server) {
            throw new MCPServerNotFoundException($serverId);
        }

        $request = new MCPRequest($method, $params, ++$this->requestId);
        
        return $this->makeRequest($server, $request);
    }

    /**
     * Initialize connection with MCP server
     */
    public function initialize(string $serverId): MCPResponse
    {
        $params = [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => ['listChanged' => true],
                'resources' => ['subscribe' => true, 'listChanged' => true],
                'prompts' => ['listChanged' => true],
                'samples' => ['listChanged' => true],
            ],
            'clientInfo' => [
                'name' => 'LaraFlowAI',
                'version' => '1.0.0'
            ]
        ];

        $response = $this->execute($serverId, MCPMethods::INITIALIZE, $params);
        
        if ($response->isSuccess()) {
            $this->capabilities[$serverId] = $response->getResult()['capabilities'] ?? [];
        }

        return $response;
    }

    /**
     * Get available tools from a server
     */
    public function getTools(string $serverId): array
    {
        $cacheKey = "mcp_tools_{$serverId}";
        $cacheTtl = $this->config['cache_tools_ttl'] ?? 3600;
        
        return Cache::remember($cacheKey, $cacheTtl, function () use ($serverId) {
            try {
                $response = $this->execute($serverId, MCPMethods::TOOLS_LIST);
                
                if ($response->isError()) {
                    throw new MCPExecutionException(
                        $response->getErrorMessage() ?? 'Failed to get tools',
                        $response->getErrorCode()
                    );
                }

                $tools = $response->getResult()['tools'] ?? [];
                
                return array_map(
                    fn($tool) => MCPTool::fromArray($tool, $serverId),
                    $tools
                );
            } catch (\Exception $e) {
                Log::error('LaraFlowAI: Failed to get server tools', [
                    'server_id' => $serverId,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Call a tool on a server
     */
    public function callTool(string $serverId, string $toolName, array $arguments = []): MCPResponse
    {
        $params = [
            'name' => $toolName,
            'arguments' => $arguments
        ];

        return $this->execute($serverId, MCPMethods::TOOLS_CALL, $params);
    }

    /**
     * Get available resources from a server
     */
    public function getResources(string $serverId): array
    {
        $cacheKey = "mcp_resources_{$serverId}";
        $cacheTtl = $this->config['cache_resources_ttl'] ?? 3600;
        
        return Cache::remember($cacheKey, $cacheTtl, function () use ($serverId) {
            try {
                $response = $this->execute($serverId, MCPMethods::RESOURCES_LIST);
                
                if ($response->isError()) {
                    throw new MCPExecutionException(
                        $response->getErrorMessage() ?? 'Failed to get resources',
                        $response->getErrorCode()
                    );
                }

                $resources = $response->getResult()['resources'] ?? [];
                
                return array_map(
                    fn($resource) => MCPResource::fromArray($resource, $serverId),
                    $resources
                );
            } catch (\Exception $e) {
                Log::error('LaraFlowAI: Failed to get server resources', [
                    'server_id' => $serverId,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Read a resource from a server
     */
    public function readResource(string $serverId, string $uri): MCPResponse
    {
        $params = ['uri' => $uri];
        return $this->execute($serverId, MCPMethods::RESOURCES_READ, $params);
    }

    /**
     * Make HTTP request to MCP server
     */
    protected function makeRequest(MCPServerConfig $server, MCPRequest $request): MCPResponse
    {
        $retryAttempts = $this->config['retry_attempts'] ?? 3;
        $retryDelay = $this->config['retry_delay'] ?? 1000;

        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            try {
                if ($this->shouldLogRequests()) {
                    Log::info('LaraFlowAI: MCP request', [
                        'server' => $server->name,
                        'method' => $request->method,
                        'attempt' => $attempt,
                        'url' => $server->url,
                        'request_id' => $request->id
                    ]);
                }

                $response = Http::withHeaders($server->getAllHeaders())
                    ->timeout($server->timeout)
                    ->post($server->url, $request->toArray());

                if ($response->successful()) {
                    $data = $response->json();
                    $mcpResponse = MCPResponse::fromArray($data);
                    
                    if ($this->shouldLogResponses()) {
                        Log::debug('LaraFlowAI: MCP response', [
                            'server' => $server->name,
                            'method' => $request->method,
                            'status' => $response->status(),
                            'success' => $mcpResponse->isSuccess(),
                            'request_id' => $request->id
                        ]);
                    }

                    if ($mcpResponse->isError()) {
                        throw new MCPExecutionException(
                            $mcpResponse->getErrorMessage() ?? 'Unknown MCP error',
                            $mcpResponse->getErrorCode(),
                            $mcpResponse->getError()
                        );
                    }

                    return $mcpResponse;
                }

                if ($attempt < $retryAttempts) {
                    Log::warning('LaraFlowAI: MCP request failed, retrying', [
                        'server' => $server->name,
                        'attempt' => $attempt,
                        'status' => $response->status(),
                        'error' => $response->body(),
                        'method' => $request->method
                    ]);
                    
                    usleep($retryDelay * 1000);
                    continue;
                }

                throw new MCPConnectionException(
                    "MCP server request failed: HTTP {$response->status()} - {$response->body()}",
                    ['status' => $response->status(), 'body' => $response->body()]
                );

            } catch (MCPException $e) {
                throw $e;
            } catch (\Exception $e) {
                if ($attempt < $retryAttempts) {
                    Log::warning('LaraFlowAI: MCP request exception, retrying', [
                        'server' => $server->name,
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                        'method' => $request->method
                    ]);
                    
                    usleep($retryDelay * 1000);
                    continue;
                }

                Log::error('LaraFlowAI: MCP request failed after all retries', [
                    'server' => $server->name,
                    'method' => $request->method,
                    'attempts' => $attempt,
                    'error' => $e->getMessage()
                ]);

                throw new MCPConnectionException(
                    "MCP request failed after all retries: {$e->getMessage()}",
                    ['attempts' => $attempt],
                    $e
                );
            }
        }

        throw new MCPConnectionException('MCP request failed after all retry attempts');
    }

    /**
     * Get available prompts from a server
     */
    public function getPrompts(string $serverId): array
    {
        $cacheKey = "mcp_prompts_{$serverId}";
        $cacheTtl = $this->config['cache_prompts_ttl'] ?? 3600;
        
        return Cache::remember($cacheKey, $cacheTtl, function () use ($serverId) {
            try {
                $response = $this->execute($serverId, MCPMethods::PROMPTS_LIST);
                
                if ($response->isError()) {
                    throw new MCPExecutionException(
                        $response->getErrorMessage() ?? 'Failed to get prompts',
                        $response->getErrorCode()
                    );
                }

                return $response->getResult()['prompts'] ?? [];
            } catch (\Exception $e) {
                Log::error('LaraFlowAI: Failed to get server prompts', [
                    'server_id' => $serverId,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Get a specific prompt from a server
     */
    public function getPrompt(string $serverId, string $promptName, array $arguments = []): MCPResponse
    {
        $params = [
            'name' => $promptName,
            'arguments' => $arguments
        ];

        return $this->execute($serverId, MCPMethods::PROMPTS_GET, $params);
    }

    /**
     * Get available samples from a server
     */
    public function getSamples(string $serverId): array
    {
        $cacheKey = "mcp_samples_{$serverId}";
        $cacheTtl = $this->config['cache_samples_ttl'] ?? 3600;
        
        return Cache::remember($cacheKey, $cacheTtl, function () use ($serverId) {
            try {
                $response = $this->execute($serverId, MCPMethods::SAMPLES_LIST);
                
                if ($response->isError()) {
                    throw new MCPExecutionException(
                        $response->getErrorMessage() ?? 'Failed to get samples',
                        $response->getErrorCode()
                    );
                }

                return $response->getResult()['samples'] ?? [];
            } catch (\Exception $e) {
                Log::error('LaraFlowAI: Failed to get server samples', [
                    'server_id' => $serverId,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Get a specific sample from a server
     */
    public function getSample(string $serverId, string $sampleName, array $arguments = []): MCPResponse
    {
        $params = [
            'name' => $sampleName,
            'arguments' => $arguments
        ];

        return $this->execute($serverId, MCPMethods::SAMPLES_GET, $params);
    }

    /**
     * Ping a server
     */
    public function ping(string $serverId): MCPResponse
    {
        return $this->execute($serverId, MCPMethods::PING);
    }

    /**
     * Refresh cache for a server
     */
    public function refreshCache(string $serverId): void
    {
        $cacheKeys = [
            "mcp_tools_{$serverId}",
            "mcp_resources_{$serverId}",
            "mcp_prompts_{$serverId}",
            "mcp_samples_{$serverId}",
            "mcp_health_{$serverId}",
            "mcp_capabilities_{$serverId}"
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Test connection to a server
     */
    public function testConnection(string $serverId): bool
    {
        try {
            $server = $this->getServer($serverId);
            
            if (!$server) {
                return false;
            }

            $response = $this->ping($serverId);
            return $response->isSuccess();
        } catch (\Exception $e) {
            Log::error('MCP connection test failed', [
                'server_id' => $serverId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get health status of all servers
     */
    public function getHealthStatus(): array
    {
        $status = [];
        
        foreach (array_keys($this->servers) as $serverId) {
            $status[$serverId] = $this->getServerHealth($serverId);
        }
        
        return $status;
    }

    /**
     * Get health status of a specific server
     */
    public function getServerHealth(string $serverId): array
    {
        $cacheKey = "mcp_health_{$serverId}";
        $cacheTtl = 60; // Cache health status for 1 minute
        
        return Cache::remember($cacheKey, $cacheTtl, function () use ($serverId) {
            try {
                $server = $this->getServer($serverId);
                $isConnected = $this->testConnection($serverId);
                
                return [
                    'status' => $isConnected ? 'healthy' : 'unhealthy',
                    'server_name' => $server?->name ?? 'Unknown',
                    'url' => $server?->url ?? 'Unknown',
                    'enabled' => $server?->enabled ?? false,
                    'tools_count' => count($this->getTools($serverId)),
                    'resources_count' => count($this->getResources($serverId)),
                    'prompts_count' => count($this->getPrompts($serverId)),
                    'samples_count' => count($this->getSamples($serverId)),
                    'capabilities' => $this->capabilities[$serverId] ?? [],
                    'last_check' => now()->toISOString(),
                    'error' => null
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'server_name' => 'Unknown',
                    'url' => 'Unknown',
                    'enabled' => false,
                    'tools_count' => 0,
                    'resources_count' => 0,
                    'prompts_count' => 0,
                    'samples_count' => 0,
                    'capabilities' => [],
                    'last_check' => now()->toISOString(),
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Get all available tools from all servers
     */
    public function getAllTools(): array
    {
        $allTools = [];
        
        foreach (array_keys($this->servers) as $serverId) {
            $tools = $this->getTools($serverId);
            $allTools[$serverId] = $tools;
        }
        
        return $allTools;
    }

    /**
     * Get all available resources from all servers
     */
    public function getAllResources(): array
    {
        $allResources = [];
        
        foreach (array_keys($this->servers) as $serverId) {
            $resources = $this->getResources($serverId);
            $allResources[$serverId] = $resources;
        }
        
        return $allResources;
    }

    /**
     * Get all available prompts from all servers
     */
    public function getAllPrompts(): array
    {
        $allPrompts = [];
        
        foreach (array_keys($this->servers) as $serverId) {
            $prompts = $this->getPrompts($serverId);
            $allPrompts[$serverId] = $prompts;
        }
        
        return $allPrompts;
    }

    /**
     * Get all available samples from all servers
     */
    public function getAllSamples(): array
    {
        $allSamples = [];
        
        foreach (array_keys($this->servers) as $serverId) {
            $samples = $this->getSamples($serverId);
            $allSamples[$serverId] = $samples;
        }
        
        return $allSamples;
    }

    /**
     * Check if logging is enabled
     */
    protected function shouldLogRequests(): bool
    {
        return $this->config['logging']['enabled'] ?? true && 
               $this->config['logging']['log_requests'] ?? true;
    }

    /**
     * Check if response logging is enabled
     */
    protected function shouldLogResponses(): bool
    {
        return $this->config['logging']['enabled'] ?? true && 
               $this->config['logging']['log_responses'] ?? false;
    }

    /**
     * Get server statistics
     */
    public function getServerStats(string $serverId): array
    {
        $server = $this->getServer($serverId);
        if (!$server) {
            return [];
        }

        $health = $this->getServerHealth($serverId);

        return [
            'server_id' => $serverId,
            'name' => $server->name,
            'url' => $server->url,
            'enabled' => $server->enabled,
            'tools_count' => $health['tools_count'],
            'resources_count' => $health['resources_count'],
            'prompts_count' => $health['prompts_count'],
            'samples_count' => $health['samples_count'],
            'health_status' => $health['status'],
            'last_health_check' => $health['last_check'],
            'version' => $server->version ?? 'unknown',
            'timeout' => $server->timeout,
            'capabilities' => $health['capabilities'],
        ];
    }

    /**
     * Get all server statistics
     */
    public function getAllServerStats(): array
    {
        $stats = [];
        
        foreach (array_keys($this->servers) as $serverId) {
            $stats[$serverId] = $this->getServerStats($serverId);
        }
        
        return $stats;
    }

    /**
     * Add or update server configuration
     */
    public function addServer(string $serverId, array $config): void
    {
        $this->servers[$serverId] = MCPServerConfig::fromArray($serverId, $config);
    }

    /**
     * Remove server configuration
     */
    public function removeServer(string $serverId): void
    {
        unset($this->servers[$serverId]);
        unset($this->capabilities[$serverId]);
        
        $this->refreshCache($serverId);
    }

    /**
     * Clear all caches
     */
    public function clearCaches(): void
    {
        foreach (array_keys($this->servers) as $serverId) {
            $this->refreshCache($serverId);
        }
    }

    /**
     * Get server capabilities
     */
    public function getServerCapabilities(string $serverId): array
    {
        return $this->capabilities[$serverId] ?? [];
    }

    /**
     * Check if server supports a specific capability
     */
    public function supportsCapability(string $serverId, string $capability): bool
    {
        $capabilities = $this->getServerCapabilities($serverId);
        return isset($capabilities[$capability]);
    }
}
