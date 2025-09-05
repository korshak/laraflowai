<?php

namespace LaraFlowAI\Tests\Unit;

use LaraFlowAI\Tests\TestCase;
use LaraFlowAI\Agent;
use LaraFlowAI\Task;
use LaraFlowAI\Memory\MemoryManager;
use Mockery;

class AgentTest extends TestCase
{
    public function test_agent_can_be_created()
    {
        $memory = Mockery::mock(MemoryManager::class);
        $provider = Mockery::mock(\LaraFlowAI\Contracts\ProviderContract::class);
        
        $agent = new Agent('Test Agent', 'Test Goal', $provider, $memory);
        
        $this->assertEquals('Test Agent', $agent->getRole());
        $this->assertEquals('Test Goal', $agent->getGoal());
    }

    public function test_agent_can_add_tools()
    {
        $memory = Mockery::mock(MemoryManager::class);
        $provider = Mockery::mock(\LaraFlowAI\Contracts\ProviderContract::class);
        $tool = Mockery::mock(\LaraFlowAI\Contracts\ToolContract::class);
        
        $tool->shouldReceive('getName')->andReturn('test-tool');
        
        $agent = new Agent('Test Agent', 'Test Goal', $provider, $memory);
        $agent->addTool($tool);
        
        $tools = $agent->getTools();
        $this->assertCount(1, $tools);
        $this->assertArrayHasKey('test-tool', $tools);
    }

    public function test_agent_can_handle_task()
    {
        $memory = Mockery::mock(MemoryManager::class);
        $provider = Mockery::mock(\LaraFlowAI\Contracts\ProviderContract::class);
        
        $provider->shouldReceive('generate')
            ->once()
            ->andReturn('Test response');
        
        $memory->shouldReceive('search')
            ->once()
            ->andReturn([]);
        
        $memory->shouldReceive('store')
            ->once();
        
        $agent = new Agent('Test Agent', 'Test Goal', $provider, $memory);
        $task = new Task('Test task description');
        
        $response = $agent->handle($task);
        
        $this->assertInstanceOf(\LaraFlowAI\Response::class, $response);
        $this->assertEquals('Test response', $response->getContent());
    }
}
