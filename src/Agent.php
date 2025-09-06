<?php

namespace LaraFlowAI;

use LaraFlowAI\Contracts\ProviderContract;
use LaraFlowAI\Contracts\MemoryContract;
use LaraFlowAI\Contracts\ToolContract;
use LaraFlowAI\Validation\InputSanitizer;
use Illuminate\Support\Facades\Log;

/**
 * Agent class represents an AI agent with a specific role and goal.
 * 
 * An agent is a core component of the LaraFlowAI system that can handle tasks,
 * use tools, and maintain memory. Each agent has a defined role (e.g., "writer", 
 * "researcher") and a goal that guides its behavior.
 * 
 * @package LaraFlowAI
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class Agent
{
    /**
     * The role of the agent (e.g., "writer", "researcher", "analyst").
     * 
     * @var string
     */
    protected string $role;

    /**
     * The goal or objective of the agent.
     * 
     * @var string
     */
    protected string $goal;

    /**
     * Array of tools available to the agent.
     * 
     * @var array<string, ToolContract>
     */
    protected array $tools = [];

    /**
     * Memory contract for storing and retrieving agent memories.
     * 
     * @var MemoryContract
     */
    protected MemoryContract $memory;

    /**
     * LLM provider contract for generating responses.
     * 
     * @var ProviderContract
     */
    protected ProviderContract $provider;

    /**
     * Additional context data for the agent.
     * 
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Configuration options for the agent.
     * 
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Create a new Agent instance.
     * 
     * @param string $role The role of the agent (e.g., "writer", "researcher")
     * @param string $goal The goal or objective of the agent
     * @param ProviderContract $provider The LLM provider for generating responses
     * @param MemoryContract $memory The memory contract for storing/retrieving memories
     * @param array<string, mixed> $config Optional configuration array
     * 
     * @throws \InvalidArgumentException If role or goal is empty or contains dangerous content
     */
    public function __construct(
        string $role,
        string $goal,
        ProviderContract $provider,
        MemoryContract $memory,
        array $config = []
    ) {
        // Sanitize inputs
        $this->role = InputSanitizer::sanitizeRole($role);
        $this->goal = InputSanitizer::sanitizeGoal($goal);
        $this->provider = $provider;
        $this->memory = $memory;
        $this->config = InputSanitizer::sanitizeArray($config);
        
        // Validate inputs
        $this->validateInputs();
    }

    /**
     * Handle a task and return a response.
     * 
     * This method processes a task by building context, generating a prompt,
     * executing tools if needed, and generating a response using the LLM provider.
     * The response and task are then stored in memory for future reference.
     * 
     * @param Task $task The task to handle
     * @return Response The generated response
     * 
     * @throws \Exception If task processing fails
     */
    public function handle(Task $task): Response
    {
        Log::info('LaraFlowAI: Agent handling task', [
            'agent_role' => $this->role,
            'task_description' => $task->getDescription()
        ]);

        // Build context from memory and task
        $context = $this->buildContext($task);
        
        // Generate prompt
        $prompt = $this->generatePrompt($task, $context);
        
        // Execute tools if needed
        $toolResults = $this->executeTools($task);
        
        // Generate response using LLM
        $response = $this->generateResponse($prompt, $toolResults);
        
        // Store in memory
        $this->storeInMemory($task, $response);
        
        return new Response($response, $this->role, $toolResults);
    }

    /**
     * Add a tool to the agent.
     * 
     * @param ToolContract $tool The tool to add
     * @return self Returns the agent instance for method chaining
     */
    public function addTool(ToolContract $tool): self
    {
        $this->tools[$tool->getName()] = $tool;
        return $this;
    }

    /**
     * Remove a tool from the agent.
     * 
     * @param string $toolName The name of the tool to remove
     * @return self Returns the agent instance for method chaining
     */
    public function removeTool(string $toolName): self
    {
        unset($this->tools[$toolName]);
        return $this;
    }

    /**
     * Get all tools available to the agent.
     * 
     * @return array<string, ToolContract> Array of tools indexed by tool name
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    /**
     * Set context for the agent.
     * 
     * @param array<string, mixed> $context The context data to set
     * @return self Returns the agent instance for method chaining
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Add a single key-value pair to the agent context.
     * 
     * @param string $key The context key
     * @param mixed $value The context value
     * @return self Returns the agent instance for method chaining
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Build context from memory and task.
     * 
     * @param Task $task The task to build context for
     * @return array<string, mixed> The built context array
     */
    protected function buildContext(Task $task): array
    {
        $taskContext = $task->getContext();
        
        // Логування для дебагу
        Log::info('LaraFlowAI: Building context', [
            'task_description' => $task->getDescription(),
            'task_context' => $taskContext,
            'task_context_count' => count($taskContext),
        ]);
        
        $context = array_merge($this->context, [
            'role' => $this->role,
            'goal' => $this->goal,
            'task' => $task->getDescription(),
            'memory' => $this->recallRelevantMemory($task),
            'task_context' => $taskContext, // Додаємо контекст з завдання
        ]);

        return $context;
    }

    /**
     * Recall relevant memory for the task.
     * 
     * @param Task $task The task to recall memory for
     * @return array<int, array<string, mixed>> Array of relevant memories
     */
    protected function recallRelevantMemory(Task $task): array
    {
        $query = $task->getDescription();
        $memories = $this->memory->search($query, 5);
        
        return array_map(function ($memory) {
            return [
                'key' => $memory['key'],
                'data' => $memory['data'],
                'relevance' => $memory['metadata']['relevance'] ?? 0,
            ];
        }, $memories);
    }

    /**
     * Generate prompt for the LLM.
     * 
     * @param Task $task The task to generate prompt for
     * @param array<string, mixed> $context The context data
     * @return string The generated prompt
     */
    protected function generatePrompt(Task $task, array $context): string
    {
        return $this->buildPrompt($task, $context);
    }

    /**
     * Validate agent inputs.
     * 
     * @throws \InvalidArgumentException If role or goal is empty or contains dangerous content
     */
    protected function validateInputs(): void
    {
        if (empty($this->role)) {
            throw new \InvalidArgumentException('Agent role cannot be empty');
        }
        
        if (empty($this->goal)) {
            throw new \InvalidArgumentException('Agent goal cannot be empty');
        }
        
        if (InputSanitizer::containsDangerousContent($this->role)) {
            throw new \InvalidArgumentException('Agent role contains potentially dangerous content');
        }
        
        if (InputSanitizer::containsDangerousContent($this->goal)) {
            throw new \InvalidArgumentException('Agent goal contains potentially dangerous content');
        }
    }

    /**
     * Build a prompt using simple string templating.
     * 
     * @param Task $task The task to build prompt for
     * @param array<string, mixed> $context The context data
     * @return string The built prompt
     */
    protected function buildPrompt(Task $task, array $context): string
    {
        // Sanitize task description
        $taskDescription = InputSanitizer::sanitizeTaskDescription($task->getDescription());
        
        $prompt = "You are a {$this->role}. Your goal is: {$this->goal}\n\n";
        $prompt .= "Task: {$taskDescription}\n\n";
        
        if (!empty($context['memory'])) {
            $prompt .= "Relevant context from memory:\n";
            foreach ($context['memory'] as $memory) {
                $memoryData = $memory['data'] ?? '';
                if (is_array($memoryData)) {
                    $memoryData = json_encode($memoryData);
                }
                $memoryData = InputSanitizer::sanitizeText((string) $memoryData);
                $prompt .= "- {$memoryData}\n";
            }
            $prompt .= "\n";
        }
        
        // Додаємо контекст з попередніх завдань
        if (!empty($context['task_context'])) {
            $prompt .= "Context from previous tasks:\n";
            foreach ($context['task_context'] as $key => $value) {
                $sanitizedKey = InputSanitizer::sanitizeText((string) $key);
                $sanitizedValue = InputSanitizer::sanitizeText((string) $value);
                $prompt .= "- {$sanitizedKey}: {$sanitizedValue}\n";
            }
            $prompt .= "\n";
        }
        
        // Логування для дебагу
        Log::info('LaraFlowAI: Task context', [
            'task_context' => $context['task_context'] ?? 'empty',
            'context_keys' => array_keys($context),
        ]);
        
        if (!empty($this->tools)) {
            $toolNames = array_map([InputSanitizer::class, 'sanitizeText'], array_keys($this->tools));
            $prompt .= "Available tools: " . implode(', ', $toolNames) . "\n\n";
        }
        
        $prompt .= "Please provide a helpful response to complete this task.";
        
        return $prompt;
    }

    /**
     * Execute tools for the task.
     * 
     * @param Task $task The task to execute tools for
     * @return array<string, mixed> Array of tool execution results
     */
    protected function executeTools(Task $task): array
    {
        $results = [];
        
        foreach ($this->tools as $tool) {
            if (!$tool->isAvailable()) {
                continue;
            }
            
            try {
                $input = $task->getToolInput($tool->getName()) ?? [];
                $result = $tool->run($input);
                $results[$tool->getName()] = $result;
                
                Log::debug('LaraFlowAI: Tool executed', [
                    'tool' => $tool->getName(),
                    'result' => is_string($result) ? substr($result, 0, 100) : gettype($result)
                ]);
            } catch (\Exception $e) {
                Log::error('LaraFlowAI: Tool execution failed', [
                    'tool' => $tool->getName(),
                    'error' => $e->getMessage()
                ]);
                
                $results[$tool->getName()] = "Error: " . $e->getMessage();
            }
        }
        
        return $results;
    }

    /**
     * Generate response using LLM.
     * 
     * @param string $prompt The prompt to send to the LLM
     * @param array<string, mixed> $toolResults The results from tool execution
     * @return string The generated response
     */
    protected function generateResponse(string $prompt, array $toolResults): string
    {
        $options = array_merge($this->config['llm_options'] ?? [], [
            'tool_results' => $toolResults
        ]);
        
        return $this->provider->generate($prompt, $options);
    }

    /**
     * Store task and response in memory.
     * 
     * @param Task $task The task that was processed
     * @param string $response The response that was generated
     */
    protected function storeInMemory(Task $task, string $response): void
    {
        $key = "agent_{$this->role}_" . time();
        $data = [
            'task' => $task->getDescription(),
            'response' => $response,
            'timestamp' => now()->toISOString(),
        ];
        
        $metadata = [
            'agent_role' => $this->role,
            'agent_goal' => $this->goal,
            'relevance' => 1.0,
        ];
        
        $this->memory->store($key, $data, $metadata);
    }

    /**
     * Get the agent role.
     * 
     * @return string The agent role
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Get the agent goal.
     * 
     * @return string The agent goal
     */
    public function getGoal(): string
    {
        return $this->goal;
    }

    /**
     * Get the agent configuration.
     * 
     * @return array<string, mixed> The agent configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set the agent configuration.
     * 
     * @param array<string, mixed> $config The configuration to set
     * @return self Returns the agent instance for method chaining
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }
}
