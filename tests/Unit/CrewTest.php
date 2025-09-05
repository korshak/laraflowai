<?php

namespace LaraFlowAI\Tests\Unit;

use LaraFlowAI\Tests\TestCase;
use LaraFlowAI\Crew;
use LaraFlowAI\Agent;
use LaraFlowAI\Task;
use LaraFlowAI\Memory\MemoryManager;
use Mockery;

class CrewTest extends TestCase
{
    public function test_crew_can_be_created()
    {
        $memory = Mockery::mock(MemoryManager::class);
        $crew = new Crew($memory);
        
        $this->assertInstanceOf(Crew::class, $crew);
    }

    public function test_crew_can_add_agents()
    {
        $memory = Mockery::mock(MemoryManager::class);
        $provider = Mockery::mock(\LaraFlowAI\Contracts\ProviderContract::class);
        
        $agent = new Agent('Test Agent', 'Test Goal', $provider, $memory);
        $crew = new Crew($memory);
        
        $crew->addAgent($agent);
        
        $agents = $crew->getAgents();
        $this->assertCount(1, $agents);
        $this->assertArrayHasKey('Test Agent', $agents);
    }

    public function test_crew_can_add_tasks()
    {
        $memory = Mockery::mock(MemoryManager::class);
        $crew = new Crew($memory);
        
        $task = new Task('Test task');
        $crew->addTask($task);
        
        $tasks = $crew->getTasks();
        $this->assertCount(1, $tasks);
        $this->assertInstanceOf(Task::class, $tasks[0]);
    }

    public function test_crew_can_execute_sequentially()
    {
        $memory = Mockery::mock(MemoryManager::class);
        $provider = Mockery::mock(\LaraFlowAI\Contracts\ProviderContract::class);
        
        $provider->shouldReceive('generate')
            ->twice()
            ->andReturn('Test response');
        
        $memory->shouldReceive('search')
            ->twice()
            ->andReturn([]);
        
        $memory->shouldReceive('store')
            ->twice();
        
        $agent = new Agent('Test Agent', 'Test Goal', $provider, $memory);
        $crew = new Crew($memory, ['execution_mode' => 'sequential']);
        
        $crew->addAgent($agent);
        $crew->addTask(new Task('Task 1'));
        $crew->addTask(new Task('Task 2'));
        
        $result = $crew->kickoff();
        
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(2, $result->getTaskCount());
    }
}
