<?php

namespace LaraFlowAI\Tests\Unit;

use LaraFlowAI\Tests\TestCase;
use LaraFlowAI\Tools\MCPTool;
use LaraFlowAI\MCP\MCPClient;
use Mockery;

class MCPToolTest extends TestCase
{
    public function test_mcp_tool_can_be_created()
    {
        $mcpTool = new MCPTool();
        
        $this->assertInstanceOf(MCPTool::class, $mcpTool);
        $this->assertEquals('mcp', $mcpTool->getName());
        $this->assertStringContainsString('MCP', $mcpTool->getDescription());
    }

    public function test_mcp_tool_can_execute_action()
    {
        $mcpClient = Mockery::mock(MCPClient::class);
        $mcpClient->shouldReceive('hasServer')
            ->with('test_server')
            ->andReturn(true);
        
        $mcpClient->shouldReceive('execute')
            ->with('test_server', 'test_action', [])
            ->andReturn(['success' => true, 'data' => 'test result']);

        $this->app->instance(MCPClient::class, $mcpClient);

        $mcpTool = new MCPTool();
        
        $input = [
            'server' => 'test_server',
            'action' => 'test_action',
            'parameters' => []
        ];

        $result = $mcpTool->run($input);
        
        $this->assertEquals(['success' => true, 'data' => 'test result'], $result);
    }

    public function test_mcp_tool_throws_exception_for_invalid_server()
    {
        $mcpClient = Mockery::mock(MCPClient::class);
        $mcpClient->shouldReceive('hasServer')
            ->with('invalid_server')
            ->andReturn(false);

        $this->app->instance(MCPClient::class, $mcpClient);

        $mcpTool = new MCPTool();
        
        $input = [
            'server' => 'invalid_server',
            'action' => 'test_action',
            'parameters' => []
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("MCP server 'invalid_server' is not configured or not enabled");

        $mcpTool->run($input);
    }

    public function test_mcp_tool_validates_input()
    {
        $mcpTool = new MCPTool();
        
        // Missing required server field
        $input = [
            'action' => 'test_action'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid input for MCP tool');

        $mcpTool->run($input);
    }

    public function test_mcp_tool_can_get_available_tools()
    {
        $mcpClient = Mockery::mock(MCPClient::class);
        $mcpClient->shouldReceive('getAllTools')
            ->andReturn([
                'server1' => [
                    ['name' => 'tool1', 'description' => 'Tool 1'],
                    ['name' => 'tool2', 'description' => 'Tool 2']
                ],
                'server2' => [
                    ['name' => 'tool3', 'description' => 'Tool 3']
                ]
            ]);

        $this->app->instance(MCPClient::class, $mcpClient);

        $mcpTool = new MCPTool();
        $tools = $mcpTool->getAvailableTools();
        
        $this->assertIsArray($tools);
        $this->assertArrayHasKey('server1', $tools);
        $this->assertArrayHasKey('server2', $tools);
        $this->assertCount(2, $tools['server1']);
        $this->assertCount(1, $tools['server2']);
    }

    public function test_mcp_tool_can_get_server_tools()
    {
        $mcpClient = Mockery::mock(MCPClient::class);
        $mcpClient->shouldReceive('getServerTools')
            ->with('test_server')
            ->andReturn([
                ['name' => 'tool1', 'description' => 'Tool 1'],
                ['name' => 'tool2', 'description' => 'Tool 2']
            ]);

        $this->app->instance(MCPClient::class, $mcpClient);

        $mcpTool = new MCPTool();
        $tools = $mcpTool->getServerTools('test_server');
        
        $this->assertIsArray($tools);
        $this->assertCount(2, $tools);
        $this->assertEquals('tool1', $tools[0]['name']);
        $this->assertEquals('tool2', $tools[1]['name']);
    }

    public function test_mcp_tool_can_test_connection()
    {
        $mcpClient = Mockery::mock(MCPClient::class);
        $mcpClient->shouldReceive('testConnection')
            ->with('test_server')
            ->andReturn(true);

        $this->app->instance(MCPClient::class, $mcpClient);

        $mcpTool = new MCPTool();
        $result = $mcpTool->testConnection('test_server');
        
        $this->assertTrue($result);
    }

    public function test_mcp_tool_can_get_health_status()
    {
        $mcpClient = Mockery::mock(MCPClient::class);
        $mcpClient->shouldReceive('getHealthStatus')
            ->andReturn([
                'server1' => ['status' => 'healthy', 'uptime' => '1h'],
                'server2' => ['status' => 'error', 'error' => 'Connection failed']
            ]);

        $this->app->instance(MCPClient::class, $mcpClient);

        $mcpTool = new MCPTool();
        $health = $mcpTool->getHealthStatus();
        
        $this->assertIsArray($health);
        $this->assertArrayHasKey('server1', $health);
        $this->assertArrayHasKey('server2', $health);
        $this->assertEquals('healthy', $health['server1']['status']);
        $this->assertEquals('error', $health['server2']['status']);
    }

    public function test_mcp_tool_can_refresh_tools()
    {
        $mcpClient = Mockery::mock(MCPClient::class);
        $mcpClient->shouldReceive('clearCaches')
            ->once();

        $this->app->instance(MCPClient::class, $mcpClient);

        $mcpTool = new MCPTool();
        $mcpTool->refreshTools();
        
        // If we get here without exception, the method worked
        $this->assertTrue(true);
    }

    public function test_mcp_tool_can_set_server_config()
    {
        $mcpTool = new MCPTool();
        
        $config = ['timeout' => 60, 'retry_attempts' => 5];
        $result = $mcpTool->setServerConfig($config);
        
        $this->assertInstanceOf(MCPTool::class, $result);
        $this->assertEquals($config, $mcpTool->getServerConfig());
    }

    public function test_mcp_tool_can_get_mcp_client()
    {
        $mcpClient = Mockery::mock(MCPClient::class);
        $this->app->instance(MCPClient::class, $mcpClient);

        $mcpTool = new MCPTool();
        $client = $mcpTool->getMCPClient();
        
        $this->assertInstanceOf(MCPClient::class, $client);
    }
}
