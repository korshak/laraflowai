<?php

namespace LaraFlowAI\MCP;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MCPClient
{
    protected array $config;
    protected array $servers = [];
    protected array $healthStatus = [];

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
            if ($serverConfig['enabled'] ?? false) {
                $this->servers[$serverId] = $serverConfig;
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
    public function getServer(string $serverId): ?array
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
     * Execute action on MCP server
     */
    public function execute(string $serverId, string $action, array $parameters = []): array
    {
        $server = $this->getServer($serverId);
        
        if (!$server) {
            throw new \InvalidArgumentException("MCP server '{$serverId}' not found or not enabled");
        }

        $payload = [
            'action' => $action,
            'parameters' => $parameters,
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'client' => 'LaraFlowAI',
                'version' => '1.0.0',
                'server_id' => $serverId
            ]
        ];

        return $this->makeRequest($server, $payload);
    }

    /**
     * Make HTTP request to MCP server
     */
    protected function makeRequest(array $server, array $payload): array
    {
        $url = $server['url'];
        $timeout = $server['timeout'] ?? $this->config['default_timeout'] ?? 30;
        $authToken = $server['auth_token'] ?? null;
        
        $headers = array_merge(
            $this->config['default_headers'] ?? [],
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        );

        if ($authToken) {
            $headers['Authorization'] = "Bearer {$authToken}";
        }

        $retryAttempts = $this->config['retry_attempts'] ?? 3;
        $retryDelay = $this->config['retry_delay'] ?? 1000;

        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            try {
                if ($this->shouldLogRequests()) {
                    Log::info('LaraFlowAI: MCP request', [
                        'server' => $server['name'] ?? 'unknown',
                        'action' => $payload['action'],
                        'attempt' => $attempt,
                        'url' => $url
                    ]);
                }

                $response = Http::withHeaders($headers)
                    ->timeout($timeout)
                    ->post($url, $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if ($this->shouldLogResponses()) {
                        Log::debug('LaraFlowAI: MCP response', [
                            'server' => $server['name'] ?? 'unknown',
                            'action' => $payload['action'],
                            'status' => $response->status(),
                            'data' => $data
                        ]);
                    }

                    return $data;
                }

                if ($attempt < $retryAttempts) {
                    Log::warning('LaraFlowAI: MCP request failed, retrying', [
                        'server' => $server['name'] ?? 'unknown',
                        'attempt' => $attempt,
                        'status' => $response->status(),
                        'error' => $response->body()
                    ]);
                    
                    usleep($retryDelay * 1000); // Convert to microseconds
                    continue;
                }

                throw new \Exception("MCP server request failed: HTTP {$response->status()} - {$response->body()}");

            } catch (\Exception $e) {
                if ($attempt < $retryAttempts) {
                    Log::warning('LaraFlowAI: MCP request exception, retrying', [
                        'server' => $server['name'] ?? 'unknown',
                        'attempt' => $attempt,
                        'error' => $e->getMessage()
                    ]);
                    
                    usleep($retryDelay * 1000);
                    continue;
                }

                Log::error('LaraFlowAI: MCP request failed after all retries', [
                    'server' => $server['name'] ?? 'unknown',
                    'action' => $payload['action'],
                    'attempts' => $attempt,
                    'error' => $e->getMessage()
                ]);

                throw $e;
            }
        }

        throw new \Exception('MCP request failed after all retry attempts');
    }

    /**
     * Get available tools from a server
     */
    public function getServerTools(string $serverId): array
    {
        $cacheKey = "mcp_tools_{$serverId}";
        $cacheTtl = $this->config['cache_tools_ttl'] ?? 3600;
        
        return Cache::remember($cacheKey, $cacheTtl, function () use ($serverId) {
            try {
                $response = $this->execute($serverId, 'list_tools');
                return $response['tools'] ?? [];
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
     * Refresh tools cache for a server
     */
    public function refreshServerTools(string $serverId): void
    {
        $cacheKey = "mcp_tools_{$serverId}";
        Cache::forget($cacheKey);
    }

    /**
     * Test connection to a server
     */
    public function testConnection(string $serverId): bool
    {
        try {
            $response = $this->execute($serverId, 'ping');
            return $response['success'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get health status of all servers
     */
    public function getHealthStatus(): array
    {
        $status = [];
        
        foreach ($this->servers as $serverId => $server) {
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
                $response = $this->execute($serverId, 'health');
                
                return [
                    'status' => $response['status'] ?? 'unknown',
                    'uptime' => $response['uptime'] ?? null,
                    'version' => $response['version'] ?? null,
                    'tools_count' => count($this->getServerTools($serverId)),
                    'last_check' => now()->toISOString(),
                    'error' => null
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'uptime' => null,
                    'version' => null,
                    'tools_count' => 0,
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
            $tools = $this->getServerTools($serverId);
            $allTools[$serverId] = $tools;
        }
        
        return $allTools;
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

        $tools = $this->getServerTools($serverId);
        $health = $this->getServerHealth($serverId);

        return [
            'server_id' => $serverId,
            'name' => $server['name'] ?? 'Unknown',
            'url' => $server['url'],
            'enabled' => $server['enabled'] ?? false,
            'tools_count' => count($tools),
            'available_actions' => $server['available_actions'] ?? [],
            'health_status' => $health['status'],
            'last_health_check' => $health['last_check'],
            'version' => $server['version'] ?? 'unknown',
            'timeout' => $server['timeout'] ?? $this->config['default_timeout'] ?? 30,
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
        $this->servers[$serverId] = $config;
    }

    /**
     * Remove server configuration
     */
    public function removeServer(string $serverId): void
    {
        unset($this->servers[$serverId]);
        
        // Clear caches
        Cache::forget("mcp_tools_{$serverId}");
        Cache::forget("mcp_health_{$serverId}");
    }

    /**
     * Clear all caches
     */
    public function clearCaches(): void
    {
        foreach (array_keys($this->servers) as $serverId) {
            Cache::forget("mcp_tools_{$serverId}");
            Cache::forget("mcp_health_{$serverId}");
        }
    }
}
