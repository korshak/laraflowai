<?php

namespace LaraFlowAI;

use LaraFlowAI\Contracts\MemoryContract;
use Illuminate\Support\Facades\Log;

/**
 * Crew class represents a collection of agents working together on tasks.
 * 
 * A crew manages multiple agents and tasks, coordinating their execution
 * either sequentially or in parallel. It provides a high-level interface
 * for orchestrating complex AI workflows.
 * 
 * @package LaraFlowAI
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class Crew
{
    /**
     * Array of agents in the crew.
     * 
     * @var array<string, Agent>
     */
    protected array $agents = [];

    /**
     * Array of tasks to be executed by the crew.
     * 
     * @var array<int, Task>
     */
    protected array $tasks = [];

    /**
     * Memory contract for storing and retrieving crew memories.
     * 
     * @var MemoryContract
     */
    protected MemoryContract $memory;

    /**
     * Configuration options for the crew.
     * 
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Results from crew execution.
     * 
     * @var array<int, array<string, mixed>>
     */
    protected array $results = [];

    /**
     * Create a new Crew instance.
     * 
     * @param MemoryContract $memory The memory contract for storing/retrieving memories
     * @param array<string, mixed> $config Optional configuration array
     */
    public function __construct(MemoryContract $memory, array $config = [])
    {
        $this->memory = $memory;
        $this->config = $config;
    }

    /**
     * Add an agent to the crew.
     * 
     * @param Agent $agent The agent to add
     * @return self Returns the crew instance for method chaining
     */
    public function addAgent(Agent $agent): self
    {
        $this->agents[$agent->getRole()] = $agent;
        return $this;
    }

    /**
     * Remove an agent from the crew.
     * 
     * @param string $role The role of the agent to remove
     * @return self Returns the crew instance for method chaining
     */
    public function removeAgent(string $role): self
    {
        unset($this->agents[$role]);
        return $this;
    }

    /**
     * Get an agent by role.
     * 
     * @param string $role The role of the agent to retrieve
     * @return Agent|null The agent if found, null otherwise
     */
    public function getAgent(string $role): ?Agent
    {
        return $this->agents[$role] ?? null;
    }

    /**
     * Get all agents in the crew.
     * 
     * @return array<string, Agent> Array of agents indexed by role
     */
    public function getAgents(): array
    {
        return $this->agents;
    }

    /**
     * Add a task to the crew.
     * 
     * @param Task $task The task to add
     * @return self Returns the crew instance for method chaining
     */
    public function addTask(Task $task): self
    {
        $this->tasks[] = $task;
        return $this;
    }

    /**
     * Add multiple tasks to the crew.
     * 
     * @param array<int, Task|array<string, mixed>> $tasks Array of tasks or task data
     * @return self Returns the crew instance for method chaining
     */
    public function addTasks(array $tasks): self
    {
        foreach ($tasks as $task) {
            if ($task instanceof Task) {
                $this->addTask($task);
            } elseif (is_array($task)) {
                $this->addTask(Task::fromArray($task));
            }
        }
        return $this;
    }

    /**
     * Get all tasks in the crew.
     * 
     * @return array<int, Task> Array of tasks
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * Execute the crew (run all tasks).
     * 
     * This method executes all tasks in the crew either sequentially or in parallel
     * based on the execution mode configuration.
     * 
     * @return CrewResult The result of the crew execution
     * 
     * @throws \Exception If crew execution fails
     */
    public function kickoff(): CrewResult
    {
        Log::info('LaraFlowAI: Starting crew execution', [
            'agents' => array_keys($this->agents),
            'tasks_count' => count($this->tasks)
        ]);

        $startTime = microtime(true);
        $this->results = [];

        try {
            if ($this->config['execution_mode'] === 'parallel') {
                $this->executeParallel();
            } else {
                $this->executeSequential();
            }

            $executionTime = microtime(true) - $startTime;

            Log::info('LaraFlowAI: Crew execution completed', [
                'execution_time' => $executionTime,
                'results_count' => count($this->results)
            ]);

            return new CrewResult($this->results, $executionTime, true);

        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;
            
            Log::error('LaraFlowAI: Crew execution failed', [
                'error' => $e->getMessage(),
                'execution_time' => $executionTime
            ]);

            return new CrewResult($this->results, $executionTime, false, $e->getMessage());
        }
    }

    /**
     * Execute tasks sequentially.
     * 
     * Tasks are executed one after another in the order they were added.
     */
    protected function executeSequential(): void
    {
        foreach ($this->tasks as $index => $task) {
            $this->executeTask($task, $index);
        }
    }

    /**
     * Execute tasks in parallel.
     * 
     * Tasks are executed simultaneously for better performance.
     */
    protected function executeParallel(): void
    {
        $promises = [];
        
        foreach ($this->tasks as $index => $task) {
            $promises[] = $this->executeTaskAsync($task, $index);
        }

        // Wait for all tasks to complete
        foreach ($promises as $promise) {
            // In a real implementation, you might use ReactPHP or similar
            // For now, we'll execute them sequentially but with shared context
            $promise();
        }
    }

    /**
     * Execute a single task.
     * 
     * @param Task $task The task to execute
     * @param int $index The index of the task in the tasks array
     * 
     * @throws \Exception If agent is not found for the task
     */
    protected function executeTask(Task $task, int $index): void
    {
        $agentRole = $task->getAgent();
        
        if (!$agentRole) {
            // Auto-assign to first available agent
            $agentRole = array_key_first($this->agents);
        }

        $agent = $this->getAgent($agentRole);
        
        if (!$agent) {
            throw new \Exception("Agent '{$agentRole}' not found in crew");
        }

        Log::info('LaraFlowAI: Executing task', [
            'task_index' => $index,
            'agent_role' => $agentRole,
            'task_description' => $task->getDescription()
        ]);

        $response = $agent->handle($task);
        
        $this->results[] = [
            'task_index' => $index,
            'task' => $task,
            'agent' => $agentRole,
            'response' => $response,
            'execution_time' => $response->getExecutionTime(),
        ];

        // Pass context to next task if sequential
        if ($this->config['execution_mode'] !== 'parallel' && isset($this->tasks[$index + 1])) {
            $nextTask = $this->tasks[$index + 1];
            $nextTask->addContext('previous_response', $response->getContent());
            $nextTask->addContext('previous_agent', $agentRole);
        }
    }

    /**
     * Execute task asynchronously (placeholder for future implementation).
     * 
     * @param Task $task The task to execute
     * @param int $index The index of the task in the tasks array
     * @return callable A callable that executes the task
     */
    protected function executeTaskAsync(Task $task, int $index): callable
    {
        return function () use ($task, $index) {
            $this->executeTask($task, $index);
        };
    }

    /**
     * Get crew configuration.
     * 
     * @return array<string, mixed> The crew configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set crew configuration.
     * 
     * @param array<string, mixed> $config The configuration to set
     * @return self Returns the crew instance for method chaining
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Get execution results.
     * 
     * @return array<int, array<string, mixed>> Array of execution results
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Clear execution results.
     * 
     * @return self Returns the crew instance for method chaining
     */
    public function clearResults(): self
    {
        $this->results = [];
        return $this;
    }

    /**
     * Execute the crew asynchronously using queues.
     * 
     * @throws \Exception If queue execution is not enabled
     */
    public function kickoffAsync(): void
    {
        if (!config('laraflowai.queue.enabled', false)) {
            throw new \Exception('Queue execution is not enabled. Set LARAFLOWAI_QUEUE_ENABLED=true in your .env file.');
        }

        $crewData = [
            'agents' => array_map(function ($agent) {
                return [
                    'role' => $agent->getRole(),
                    'goal' => $agent->getGoal(),
                    'provider' => $agent->getConfig()['provider'] ?? null,
                    'config' => $agent->getConfig(),
                    'tools' => array_map(function ($tool) {
                        return [
                            'class' => get_class($tool),
                            'config' => $tool->getConfig() ?? []
                        ];
                    }, $agent->getTools())
                ];
            }, $this->agents),
            'config' => $this->config
        ];

        $tasksData = array_map(function ($task) {
            return $task->toArray();
        }, $this->tasks);

        \LaraFlowAI\Jobs\ExecuteCrewJob::dispatch($crewData, $tasksData, $this->config)
            ->onQueue(config('laraflowai.queue.queue', 'default'))
            ->onConnection(config('laraflowai.queue.connection', 'default'));
    }
}
