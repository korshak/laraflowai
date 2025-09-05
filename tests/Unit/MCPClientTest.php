<?php

namespace LaraFlowAI\Tests\Unit;

use LaraFlowAI\Tests\TestCase;
use LaraFlowAI\MCP\MCPClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Mockery;

class MCPClientTest extends TestCase
{
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = [
            'enabled' => true,
            'default_timeout' => 30,
            'cache_tools_ttl' => 3600,
            'retry_attempts' => 3,
            'retry_delay' => 1000,
            'servers' => [
                'test_server' => [
                    'name' => 'Test Server',
                    'url' => 'http://localhost:3000/api/mcp',
                    'auth_token' => 'test-token',
                    'timeout' => 30,
                    'enabled' => true,
                    'available_actions' => ['test_action', 'ping'],
                    'description' => 'Test MCP server',
                    'version' => '1.0.0'
                ]
            ],
            'default_headers' => [
                'User-Agent' => 'LaraFlowAI/1.0.0',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'logging' => [
                'enabled' => true,
                'level' => 'info',
                'log_requests' => true,
                'log_responses' => false
            ]
        ];
    }

    public function test_mcp_client_can_be_created()
    {
        $client = new MCPClient($this->config);
        
        $this->assertInstanceOf(MCPClient::class, $client);
    }

    public function test_mcp_client_can_get_servers()
    {
        $client = new MCPClient($this->config);
        $servers = $client->getServers();
        
        $this->assertIsArray($servers);
        $this->assertArrayHasKey('test_server', $servers);
        $this->assertEquals('Test Server', $servers['test_server']['name']);
    }

    public function test_mcp_client_can_get_specific_server()
    {
        $client = new MCPClient($this->config);
        $server = $client->getServer('test_server');
        
        $this->assertIsArray($server);
        $this->assertEquals('Test Server', $server['name']);
        $this->assertEquals('http://localhost:3000/api/mcp', $server['url']);
    }

    public function test_mcp_client_returns_null_for_invalid_server()
    {
        $client = new MCPClient($this->config);
        $server = $client->getServer('invalid_server');
        
        $this->assertNull($server);
    }

    public function test_mcp_client_can_check_server_exists()
    {
        $client = new MCPClient($this->config);
        
        $this->assertTrue($client->hasServer('test_server'));
        $this->assertFalse($client->hasServer('invalid_server'));
    }

    public function test_mcp_client_can_execute_action()
    {
        Http::fake([
            'localhost:3000/api/mcp' => Http::response([
                'success' => true,
                'data' => 'test result'
            ], 200)
        ]);

        $client = new MCPClient($this->config);
        $result = $client->execute('test_server', 'test_action', ['param1' => 'value1']);
        
        $this->assertEquals([
            'success' => true,
            'data' => 'test result'
        ], $result);
    }

    public function test_mcp_client_throws_exception_for_invalid_server()
    {
        $client = new MCPClient($this->config);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("MCP server 'invalid_server' not found or not enabled");
        
        $client->execute('invalid_server', 'test_action');
    }

    public function test_mcp_client_can_get_server_tools()
    {
        Http::fake([
            'localhost:3000/api/mcp' => Http::response([
                'tools' => [
                    ['name' => 'tool1', 'description' => 'Tool 1'],
                    ['name' => 'tool2', 'description' => 'Tool 2']
                ]
            ], 200)
        ]);

        $client = new MCPClient($this->config);
        $tools = $client->getServerTools('test_server');
        
        $this->assertIsArray($tools);
        $this->assertCount(2, $tools);
        $this->assertEquals('tool1', $tools[0]['name']);
        $this->assertEquals('tool2', $tools[1]['name']);
    }

    public function test_mcp_client_caches_server_tools()
    {
        Http::fake([
            'localhost:3000/api/mcp' => Http::response([
                'tools' => [
                    ['name' => 'tool1', 'description' => 'Tool 1']
                ]
            ], 200)
        ]);

        $client = new MCPClient($this->config);
        
        // First call should make HTTP request
        $tools1 = $client->getServerTools('test_server');
        
        // Second call should use cache
        $tools2 = $client->getServerTools('test_server');
        
        $this->assertEquals($tools1, $tools2);
        
        // Should only make one HTTP request due to caching
        Http::assertSentCount(1);
    }

    public function test_mcp_client_can_test_connection()
    {
        Http::fake([
            'localhost:3000/api/mcp' => Http::response([
                'success' => true
            ], 200)
        ]);

        $client = new MCPClient($this->config);
        $result = $client->testConnection('test_server');
        
        $this->assertTrue($result);
    }

    public function test_mcp_client_returns_false_for_failed_connection()
    {
        Http::fake([
            'localhost:3000/api/mcp' => Http::response([], 500)
        ]);

        $client = new MCPClient($this->config);
        $result = $client->testConnection('test_server');
        
        $this->assertFalse($result);
    }

    public function test_mcp_client_can_get_health_status()
    {
        Http::fake([
            'localhost:3000/api/mcp' => Http::response([
                'status' => 'healthy',
                'uptime' => '1h',
                'version' => '1.0.0'
            ], 200)
        ]);

        $client = new MCPClient($this->config);
        $health = $client->getServerHealth('test_server');
        
        $this->assertIsArray($health);
        $this->assertEquals('healthy', $health['status']);
        $this->assertEquals('1h', $health['uptime']);
        $this->assertEquals('1.0.0', $health['version']);
        $this->assertArrayHasKey('last_check', $health);
    }

    public function test_mcp_client_can_get_all_tools()
    {
        Http::fake([
            'localhost:3000/api/mcp' => Http::response([
                'tools' => [
                    ['name' => 'tool1', 'description' => 'Tool 1']
                ]
            ], 200)
        ]);

        $client = new MCPClient($this->config);
        $allTools = $client->getAllTools();
        
        $this->assertIsArray($allTools);
        $this->assertArrayHasKey('test_server', $allTools);
        $this->assertCount(1, $allTools['test_server']);
    }

    public function test_mcp_client_can_add_server()
    {
        $client = new MCPClient($this->config);
        
        $newServer = [
            'name' => 'New Server',
            'url' => 'http://new-server:3000/api/mcp',
            'enabled' => true
        ];
        
        $client->addServer('new_server', $newServer);
        
        $this->assertTrue($client->hasServer('new_server'));
        $this->assertEquals('New Server', $client->getServer('new_server')['name']);
    }

    public function test_mcp_client_can_remove_server()
    {
        $client = new MCPClient($this->config);
        
        $this->assertTrue($client->hasServer('test_server'));
        
        $client->removeServer('test_server');
        
        $this->assertFalse($client->hasServer('test_server'));
    }

    public function test_mcp_client_can_clear_caches()
    {
        $client = new MCPClient($this->config);
        
        // This should not throw an exception
        $client->clearCaches();
        
        $this->assertTrue(true);
    }

    public function test_mcp_client_can_get_server_stats()
    {
        Http::fake([
            'localhost:3000/api/mcp' => Http::response([
                'tools' => [
                    ['name' => 'tool1', 'description' => 'Tool 1']
                ]
            ], 200)
        ]);

        $client = new MCPClient($this->config);
        $stats = $client->getServerStats('test_server');
        
        $this->assertIsArray($stats);
        $this->assertEquals('test_server', $stats['server_id']);
        $this->assertEquals('Test Server', $stats['name']);
        $this->assertEquals('http://localhost:3000/api/mcp', $stats['url']);
        $this->assertTrue($stats['enabled']);
        $this->assertEquals(1, $stats['tools_count']);
    }

    public function test_mcp_client_can_get_all_server_stats()
    {
        $client = new MCPClient($this->config);
        $allStats = $client->getAllServerStats();
        
        $this->assertIsArray($allStats);
        $this->assertArrayHasKey('test_server', $allStats);
        $this->assertEquals('test_server', $allStats['test_server']['server_id']);
    }
}
