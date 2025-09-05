<?php

namespace LaraFlowAI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LaraFlowAI\Flow;
use LaraFlowAI\Contracts\MemoryContract;
use Illuminate\Support\Facades\Log;

/**
 * ExecuteFlowJob handles asynchronous flow execution.
 * 
 * This job is dispatched when a flow needs to be executed asynchronously
 * using Laravel's queue system. It reconstructs the flow from serialized
 * data and executes it in the background.
 * 
 * @package LaraFlowAI\Jobs
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class ExecuteFlowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Serialized flow data.
     * 
     * @var array<string, mixed>
     */
    protected array $flowData;

    /**
     * Serialized steps data.
     * 
     * @var array<int, array<string, mixed>>
     */
    protected array $stepsData;

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
    public function __construct(array $flowData, array $stepsData, array $config = [])
    {
        $this->flowData = $flowData;
        $this->stepsData = $stepsData;
        $this->config = $config;
        $this->tries = $config['tries'] ?? 3;
        $this->timeout = $config['timeout'] ?? 600;
    }

    /**
     * Execute the job.
     */
    public function handle(MemoryContract $memory): void
    {
        Log::info('LaraFlowAI: Starting flow execution job', [
            'flow_name' => $this->flowData['name'] ?? 'unnamed',
            'steps_count' => count($this->stepsData)
        ]);

        try {
            // Reconstruct flow from serialized data
            $flow = $this->reconstructFlow($memory);
            
            // Execute flow
            $result = $flow->run();
            
            // Store result in memory or dispatch event
            $this->handleResult($result);
            
            Log::info('LaraFlowAI: Flow execution job completed', [
                'success' => $result->isSuccess(),
                'execution_time' => $result->getExecutionTime()
            ]);

        } catch (\Exception $e) {
            Log::error('LaraFlowAI: Flow execution job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Reconstruct flow from serialized data
     */
    protected function reconstructFlow(MemoryContract $memory): Flow
    {
        $flow = new Flow($memory, $this->config);
        
        // Add steps
        foreach ($this->stepsData as $stepData) {
            $step = $this->reconstructStep($stepData);
            $flow->addStep($step);
        }
        
        return $flow;
    }

    /**
     * Reconstruct step from serialized data
     */
    protected function reconstructStep(array $stepData): \LaraFlowAI\FlowStep
    {
        $step = new \LaraFlowAI\FlowStep(
            $stepData['name'],
            $stepData['type'],
            $stepData['config'] ?? []
        );
        
        // Reconstruct step-specific data
        switch ($stepData['type']) {
            case 'crew':
                if (isset($stepData['crew_data'])) {
                    $crew = $this->reconstructCrew($stepData['crew_data']);
                    $step = \LaraFlowAI\FlowStep::crew($stepData['name'], $crew, $stepData['config'] ?? []);
                }
                break;
            case 'condition':
                if (isset($stepData['condition_data'])) {
                    $condition = $this->reconstructCondition($stepData['condition_data']);
                    $step = \LaraFlowAI\FlowStep::condition($stepData['name'], $condition, $stepData['config'] ?? []);
                }
                break;
            case 'custom':
                if (isset($stepData['handler_data'])) {
                    $handler = $this->reconstructHandler($stepData['handler_data']);
                    $step = \LaraFlowAI\FlowStep::custom($stepData['name'], $handler, $stepData['config'] ?? []);
                }
                break;
        }
        
        return $step;
    }

    /**
     * Reconstruct crew from serialized data
     */
    protected function reconstructCrew(array $crewData): \LaraFlowAI\Crew
    {
        $memory = app(MemoryContract::class);
        $crew = new \LaraFlowAI\Crew($memory, $crewData['config'] ?? []);
        
        // Add agents
        foreach ($crewData['agents'] ?? [] as $agentData) {
            $agent = $this->reconstructAgent($agentData);
            $crew->addAgent($agent);
        }
        
        // Add tasks
        foreach ($crewData['tasks'] ?? [] as $taskData) {
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
        $provider = app('laraflowai.llm')->driver($agentData['provider'] ?? null);
        $memory = app(MemoryContract::class);
        
        return new \LaraFlowAI\Agent(
            $agentData['role'],
            $agentData['goal'],
            $provider,
            $memory,
            $agentData['config'] ?? []
        );
    }

    /**
     * Reconstruct condition from serialized data
     */
    protected function reconstructCondition(array $conditionData): \LaraFlowAI\FlowCondition
    {
        if (isset($conditionData['evaluator'])) {
            return \LaraFlowAI\FlowCondition::custom($conditionData['evaluator']);
        }
        
        return new \LaraFlowAI\FlowCondition(
            $conditionData['expression'],
            $conditionData['variables'] ?? []
        );
    }

    /**
     * Reconstruct handler from serialized data
     */
    protected function reconstructHandler(array $handlerData)
    {
        // This is a simplified approach - in production you might need more sophisticated serialization
        return $handlerData['callable'] ?? null;
    }

    /**
     * Handle execution result
     */
    protected function handleResult(\LaraFlowAI\FlowResult $result): void
    {
        // Store result in memory
        $memory = app(MemoryContract::class);
        $memory->store(
            'flow_result_' . time(),
            $result->toArray(),
            ['type' => 'flow_result', 'timestamp' => now()]
        );
        
        // Dispatch event if configured
        if (class_exists(\LaraFlowAI\Events\FlowExecuted::class)) {
            event(new \LaraFlowAI\Events\FlowExecuted($result));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('LaraFlowAI: Flow execution job permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Dispatch failure event if configured
        if (class_exists(\LaraFlowAI\Events\FlowExecutionFailed::class)) {
            event(new \LaraFlowAI\Events\FlowExecutionFailed($exception));
        }
    }
}
