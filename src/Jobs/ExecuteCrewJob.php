<?php

namespace LaraFlowAI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LaraFlowAI\Crew;
use LaraFlowAI\Contracts\MemoryContract;
use Illuminate\Support\Facades\Log;

/**
 * ExecuteCrewJob handles asynchronous crew execution.
 * 
 * This job is dispatched when a crew needs to be executed asynchronously
 * using Laravel's queue system. It reconstructs the crew from serialized
 * data and executes it in the background.
 * 
 * @package LaraFlowAI\Jobs
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class ExecuteCrewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Serialized crew data.
     * 
     * @var array<string, mixed>
     */
    protected array $crewData;

    /**
     * Serialized tasks data.
     * 
     * @var array<int, array<string, mixed>>
     */
    protected array $tasksData;

    /**
     * Job configuration.
     * 
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * The number of times the job may be attempted.
     * 
     * @var int
     */
    public int $tries;

    /**
     * The number of seconds the job can run before timing out.
     * 
     * @var int
     */
    public int $timeout;

    /**
     * Create a new job instance.
     */
    public function __construct(array $crewData, array $tasksData, array $config = [])
    {
        $this->crewData = $crewData;
        $this->tasksData = $tasksData;
        $this->config = $config;
        $this->tries = $config['tries'] ?? 3;
        $this->timeout = $config['timeout'] ?? 300;
    }

    /**
     * Execute the job.
     */
    public function handle(MemoryContract $memory): void
    {
        Log::info('LaraFlowAI: Starting crew execution job', [
            'crew_agents' => count($this->crewData['agents'] ?? []),
            'tasks_count' => count($this->tasksData)
        ]);

        try {
            // Reconstruct crew from serialized data
            $crew = $this->reconstructCrew($memory);
            
            // Execute crew
            $result = $crew->kickoff();
            
            // Store result in memory or dispatch event
            $this->handleResult($result);
            
            Log::info('LaraFlowAI: Crew execution job completed', [
                'success' => $result->isSuccess(),
                'execution_time' => $result->getExecutionTime()
            ]);

        } catch (\Exception $e) {
            Log::error('LaraFlowAI: Crew execution job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Reconstruct crew from serialized data
     */
    protected function reconstructCrew(MemoryContract $memory): Crew
    {
        $crew = new Crew($memory, $this->config);
        
        // Add agents
        foreach ($this->crewData['agents'] ?? [] as $agentData) {
            $agent = $this->reconstructAgent($agentData);
            $crew->addAgent($agent);
        }
        
        // Add tasks
        foreach ($this->tasksData as $taskData) {
            $task = \LaraFlowAI\Task::fromArray($taskData);
            $crew->addTask($task);
        }
        
        return $crew;
    }

    /**
     * Reconstruct agent from serialized data
     */
    protected function reconstructAgent(array $agentData): \LaraFlowAI\Agent
    {
        // Validate required fields
        if (empty($agentData['role']) || empty($agentData['goal'])) {
            throw new \InvalidArgumentException('Agent role and goal are required');
        }
        
        // Sanitize agent data
        $agentData = \LaraFlowAI\Validation\InputSanitizer::sanitizeArray($agentData);
        
        $provider = app('laraflowai.llm')->driver($agentData['provider'] ?? null);
        $memory = app(MemoryContract::class);
        
        $agent = new \LaraFlowAI\Agent(
            $agentData['role'],
            $agentData['goal'],
            $provider,
            $memory,
            $agentData['config'] ?? []
        );
        
        // Add tools
        foreach ($agentData['tools'] ?? [] as $toolData) {
            if (!is_array($toolData)) {
                continue; // Skip invalid tool data
            }
            
            $tool = $this->reconstructTool($toolData);
            $agent->addTool($tool);
        }
        
        return $agent;
    }

    /**
     * Reconstruct tool from serialized data
     */
    protected function reconstructTool(array $toolData): \LaraFlowAI\Contracts\ToolContract
    {
        $toolClass = $toolData['class'];
        
        // Validate tool class exists and implements correct interface
        if (!class_exists($toolClass)) {
            throw new \InvalidArgumentException("Tool class {$toolClass} not found");
        }
        
        if (!is_subclass_of($toolClass, \LaraFlowAI\Contracts\ToolContract::class)) {
            throw new \InvalidArgumentException("Tool class {$toolClass} does not implement ToolContract");
        }
        
        // Only allow specific tool classes for security
        $allowedTools = [
            \LaraFlowAI\Tools\HttpTool::class,
            \LaraFlowAI\Tools\DatabaseTool::class,
            \LaraFlowAI\Tools\FilesystemTool::class,
            \LaraFlowAI\Tools\MCPTool::class,
        ];
        
        if (!in_array($toolClass, $allowedTools)) {
            throw new \InvalidArgumentException("Tool class {$toolClass} is not allowed for queue execution");
        }
        
        // Sanitize tool configuration
        $config = \LaraFlowAI\Validation\InputSanitizer::sanitizeArray($toolData['config'] ?? []);
        
        return new $toolClass($config);
    }

    /**
     * Handle execution result
     */
    protected function handleResult(\LaraFlowAI\CrewResult $result): void
    {
        // Store result in memory
        $memory = app(MemoryContract::class);
        $memory->store(
            'crew_result_' . time(),
            $result->toArray(),
            ['type' => 'crew_result', 'timestamp' => now()]
        );
        
        // Dispatch event if configured
        if (class_exists(\LaraFlowAI\Events\CrewExecuted::class)) {
            event(new \LaraFlowAI\Events\CrewExecuted($result));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('LaraFlowAI: Crew execution job permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Dispatch failure event if configured
        if (class_exists(\LaraFlowAI\Events\CrewExecutionFailed::class)) {
            event(new \LaraFlowAI\Events\CrewExecutionFailed($exception));
        }
    }
}
