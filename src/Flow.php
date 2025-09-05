<?php

namespace LaraFlowAI;

use LaraFlowAI\Contracts\MemoryContract;
use Illuminate\Support\Facades\Log;

/**
 * Flow class represents a workflow of steps that can be executed in sequence.
 * 
 * A flow manages multiple steps, conditions, and events to create complex
 * AI workflows. It provides a high-level interface for orchestrating
 * multi-step processes with conditional logic and event handling.
 * 
 * @package LaraFlowAI
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class Flow
{
    /**
     * Array of steps in the flow.
     * 
     * @var array<int, FlowStep>
     */
    protected array $steps = [];

    /**
     * Array of global conditions for the flow.
     * 
     * @var array<int, FlowCondition>
     */
    protected array $conditions = [];

    /**
     * Array of event handlers.
     * 
     * @var array<string, callable>
     */
    protected array $events = [];

    /**
     * Memory contract for storing and retrieving flow memories.
     * 
     * @var MemoryContract
     */
    protected MemoryContract $memory;

    /**
     * Configuration options for the flow.
     * 
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Context data shared across flow steps.
     * 
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Results from flow execution.
     * 
     * @var array<int, array<string, mixed>>
     */
    protected array $results = [];

    /**
     * Create a new Flow instance.
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
     * Add a step to the flow.
     * 
     * @param FlowStep $step The step to add
     * @return self Returns the flow instance for method chaining
     */
    public function addStep(FlowStep $step): self
    {
        $this->steps[] = $step;
        return $this;
    }

    /**
     * Add multiple steps to the flow.
     * 
     * @param array<int, FlowStep|array<string, mixed>> $steps Array of steps or step data
     * @return self Returns the flow instance for method chaining
     */
    public function addSteps(array $steps): self
    {
        foreach ($steps as $step) {
            if ($step instanceof FlowStep) {
                $this->addStep($step);
            } elseif (is_array($step)) {
                $this->addStep(FlowStep::fromArray($step));
            }
        }
        return $this;
    }

    /**
     * Add a condition to the flow.
     * 
     * @param FlowCondition $condition The condition to add
     * @return self Returns the flow instance for method chaining
     */
    public function addCondition(FlowCondition $condition): self
    {
        $this->conditions[] = $condition;
        return $this;
    }

    /**
     * Add an event handler.
     * 
     * @param string $event The event name
     * @param callable $handler The event handler function
     * @return self Returns the flow instance for method chaining
     */
    public function onEvent(string $event, callable $handler): self
    {
        $this->events[$event] = $handler;
        return $this;
    }

    /**
     * Run the flow.
     * 
     * This method executes all steps in the flow in sequence, evaluating
     * conditions and triggering events as appropriate.
     * 
     * @return FlowResult The result of the flow execution
     * 
     * @throws \Exception If flow execution fails
     */
    public function run(): FlowResult
    {
        Log::info('LaraFlowAI: Starting flow execution', [
            'steps_count' => count($this->steps),
            'conditions_count' => count($this->conditions)
        ]);

        $startTime = microtime(true);
        $this->results = [];
        $this->context = [];

        try {
            $this->executeSteps();
            $executionTime = microtime(true) - $startTime;

            Log::info('LaraFlowAI: Flow execution completed', [
                'execution_time' => $executionTime,
                'results_count' => count($this->results)
            ]);

            return new FlowResult($this->results, $executionTime, true);

        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;
            
            Log::error('LaraFlowAI: Flow execution failed', [
                'error' => $e->getMessage(),
                'execution_time' => $executionTime
            ]);

            return new FlowResult($this->results, $executionTime, false, $e->getMessage());
        }
    }

    /**
     * Execute all steps in the flow.
     * 
     * Steps are executed in the order they were added.
     */
    protected function executeSteps(): void
    {
        foreach ($this->steps as $index => $step) {
            $this->executeStep($step, $index);
        }
    }

    /**
     * Execute a single step.
     * 
     * @param FlowStep $step The step to execute
     * @param int $index The index of the step in the steps array
     * 
     * @throws \Exception If step execution fails and continue_on_error is false
     */
    protected function executeStep(FlowStep $step, int $index): void
    {
        Log::info('LaraFlowAI: Executing flow step', [
            'step_index' => $index,
            'step_type' => $step->getType(),
            'step_name' => $step->getName()
        ]);

        // Check conditions before executing
        if (!$this->evaluateConditions($step)) {
            Log::info('LaraFlowAI: Step conditions not met, skipping', [
                'step_index' => $index
            ]);
            return;
        }

        $startTime = microtime(true);
        $result = null;

        try {
            switch ($step->getType()) {
                case 'crew':
                    $result = $this->executeCrewStep($step);
                    break;
                case 'condition':
                    $result = $this->executeConditionStep($step);
                    break;
                case 'delay':
                    $result = $this->executeDelayStep($step);
                    break;
                case 'custom':
                    $result = $this->executeCustomStep($step);
                    break;
                default:
                    throw new \Exception("Unknown step type: {$step->getType()}");
            }

            $executionTime = microtime(true) - $startTime;

            $this->results[] = [
                'step_index' => $index,
                'step' => $step,
                'result' => $result,
                'execution_time' => $executionTime,
                'success' => true,
            ];

            // Update context with step result
            $this->context[$step->getName()] = $result;

            // Trigger events
            $this->triggerEvent('step_completed', [
                'step' => $step,
                'result' => $result,
                'execution_time' => $executionTime,
            ]);

        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;
            
            Log::error('LaraFlowAI: Step execution failed', [
                'step_index' => $index,
                'error' => $e->getMessage()
            ]);

            $this->results[] = [
                'step_index' => $index,
                'step' => $step,
                'result' => null,
                'execution_time' => $executionTime,
                'success' => false,
                'error' => $e->getMessage(),
            ];

            // Trigger error event
            $this->triggerEvent('step_failed', [
                'step' => $step,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime,
            ]);

            // Check if we should continue on error
            if (!$step->getConfig()['continue_on_error'] ?? false) {
                throw $e;
            }
        }
    }

    /**
     * Execute a crew step.
     * 
     * @param FlowStep $step The crew step to execute
     * @return CrewResult The result of the crew execution
     * 
     * @throws \Exception If crew is not found for the step
     */
    protected function executeCrewStep(FlowStep $step): CrewResult
    {
        $crew = $step->getCrew();
        if (!$crew) {
            throw new \Exception("Crew not found for step: {$step->getName()}");
        }

        return $crew->kickoff();
    }

    /**
     * Execute a condition step.
     * 
     * @param FlowStep $step The condition step to execute
     * @return bool The result of the condition evaluation
     * 
     * @throws \Exception If condition is not found for the step
     */
    protected function executeConditionStep(FlowStep $step): bool
    {
        $condition = $step->getCondition();
        if (!$condition) {
            throw new \Exception("Condition not found for step: {$step->getName()}");
        }

        return $condition->evaluate($this->context);
    }

    /**
     * Execute a delay step.
     * 
     * @param FlowStep $step The delay step to execute
     * @return bool Always returns true after the delay
     */
    protected function executeDelayStep(FlowStep $step): bool
    {
        $delay = $step->getConfig()['delay'] ?? 1;
        sleep($delay);
        return true;
    }

    /**
     * Execute a custom step.
     * 
     * @param FlowStep $step The custom step to execute
     * @return mixed The result of the custom handler
     * 
     * @throws \Exception If handler is not found for the step
     */
    protected function executeCustomStep(FlowStep $step): mixed
    {
        $handler = $step->getHandler();
        if (!$handler) {
            throw new \Exception("Handler not found for step: {$step->getName()}");
        }

        return $handler($this->context);
    }

    /**
     * Evaluate conditions for a step.
     * 
     * @param FlowStep $step The step to evaluate conditions for
     * @return bool True if all conditions are met, false otherwise
     */
    protected function evaluateConditions(FlowStep $step): bool
    {
        $stepConditions = $step->getConditions();
        
        foreach ($stepConditions as $condition) {
            if (!$condition->evaluate($this->context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Trigger an event.
     * 
     * @param string $event The event name
     * @param array<string, mixed> $data The event data
     */
    protected function triggerEvent(string $event, array $data): void
    {
        if (isset($this->events[$event])) {
            $this->events[$event]($data);
        }
    }

    /**
     * Get flow context.
     * 
     * @return array<string, mixed> The flow context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set flow context.
     * 
     * @param array<string, mixed> $context The context to set
     * @return self Returns the flow instance for method chaining
     */
    public function setContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    /**
     * Add a single key-value pair to the flow context.
     * 
     * @param string $key The context key
     * @param mixed $value The context value
     * @return self Returns the flow instance for method chaining
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Get flow configuration.
     * 
     * @return array<string, mixed> The flow configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set flow configuration.
     * 
     * @param array<string, mixed> $config The configuration to set
     * @return self Returns the flow instance for method chaining
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
     * @return self Returns the flow instance for method chaining
     */
    public function clearResults(): self
    {
        $this->results = [];
        return $this;
    }

    /**
     * Run the flow asynchronously using queues.
     * 
     * @throws \Exception If queue execution is not enabled
     */
    public function runAsync(): void
    {
        if (!config('laraflowai.queue.enabled', false)) {
            throw new \Exception('Queue execution is not enabled. Set LARAFLOWAI_QUEUE_ENABLED=true in your .env file.');
        }

        $flowData = [
            'name' => $this->config['name'] ?? 'unnamed',
            'config' => $this->config
        ];

        $stepsData = array_map(function ($step) {
            $stepData = $step->toArray();
            
            // Add serializable data for complex steps
            if ($step->getType() === 'crew' && $step->getCrew()) {
                $crew = $step->getCrew();
                $stepData['crew_data'] = [
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
                    }, $crew->getAgents()),
                    'tasks' => array_map(function ($task) {
                        return $task->toArray();
                    }, $crew->getTasks()),
                    'config' => $crew->getConfig()
                ];
            }
            
            if ($step->getType() === 'condition' && $step->getCondition()) {
                $condition = $step->getCondition();
                $stepData['condition_data'] = [
                    'expression' => $condition->getExpression(),
                    'variables' => $condition->getVariables(),
                    'evaluator' => $condition->getEvaluator()
                ];
            }
            
            if ($step->getType() === 'custom' && $step->getHandler()) {
                $stepData['handler_data'] = [
                    'callable' => $step->getHandler()
                ];
            }
            
            return $stepData;
        }, $this->steps);

        \LaraFlowAI\Jobs\ExecuteFlowJob::dispatch($flowData, $stepsData, $this->config)
            ->onQueue(config('laraflowai.queue.queue', 'default'))
            ->onConnection(config('laraflowai.queue.connection', 'default'));
    }
}
