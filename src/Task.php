<?php

namespace LaraFlowAI;

use LaraFlowAI\Validation\InputSanitizer;

/**
 * Task class represents a single task to be executed by an agent.
 * 
 * A task contains a description, optional agent assignment, tool inputs,
 * and context data. It provides a structured way to define work that
 * needs to be performed by AI agents.
 * 
 * @package LaraFlowAI
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class Task
{
    /**
     * The description of the task.
     * 
     * @var string
     */
    protected string $description;

    /**
     * The agent role assigned to this task.
     * 
     * @var string|null
     */
    protected ?string $agent = null;

    /**
     * Tool inputs for this task.
     * 
     * @var array<string, array<string, mixed>>
     */
    protected array $toolInputs = [];

    /**
     * Context data for this task.
     * 
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Configuration options for this task.
     * 
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Create a new Task instance.
     * 
     * @param string $description The task description
     * @param array<string, mixed> $config Optional configuration array
     * 
     * @throws \InvalidArgumentException If description is empty or contains dangerous content
     */
    public function __construct(string $description, array $config = [])
    {
        $this->description = InputSanitizer::sanitizeTaskDescription($description);
        $this->config = InputSanitizer::sanitizeArray($config);
        
        // Validate inputs
        $this->validateInputs();
    }

    /**
     * Validate task inputs.
     * 
     * @throws \InvalidArgumentException If description is empty or contains dangerous content
     */
    protected function validateInputs(): void
    {
        if (empty($this->description)) {
            throw new \InvalidArgumentException('Task description cannot be empty');
        }
        
        if (InputSanitizer::containsDangerousContent($this->description)) {
            throw new \InvalidArgumentException('Task description contains potentially dangerous content');
        }
    }

    /**
     * Set the agent for this task.
     * 
     * @param string $agent The agent role to assign
     * @return self Returns the task instance for method chaining
     */
    public function setAgent(string $agent): self
    {
        $this->agent = InputSanitizer::sanitizeText($agent, 255);
        return $this;
    }

    /**
     * Get the agent for this task.
     * 
     * @return string|null The agent role if assigned, null otherwise
     */
    public function getAgent(): ?string
    {
        return $this->agent;
    }

    /**
     * Set tool input for a specific tool.
     * 
     * @param string $toolName The name of the tool
     * @param array<string, mixed> $input The input data for the tool
     * @return self Returns the task instance for method chaining
     */
    public function setToolInput(string $toolName, array $input): self
    {
        $this->toolInputs[$toolName] = $input;
        return $this;
    }

    /**
     * Get tool input for a specific tool.
     * 
     * @param string $toolName The name of the tool
     * @return array<string, mixed>|null The tool input if set, null otherwise
     */
    public function getToolInput(string $toolName): ?array
    {
        return $this->toolInputs[$toolName] ?? null;
    }

    /**
     * Set context for the task.
     * 
     * @param array<string, mixed> $context The context data to set
     * @return self Returns the task instance for method chaining
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Add a single key-value pair to the task context.
     * 
     * @param string $key The context key
     * @param mixed $value The context value
     * @return self Returns the task instance for method chaining
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Get context for the task.
     * 
     * @return array<string, mixed> The task context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get task description.
     * 
     * @return string The task description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set task description.
     * 
     * @param string $description The task description
     * @return self Returns the task instance for method chaining
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get task configuration.
     * 
     * @return array<string, mixed> The task configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set task configuration.
     * 
     * @param array<string, mixed> $config The configuration to set
     * @return self Returns the task instance for method chaining
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Create a task from array data.
     * 
     * @param array<string, mixed> $data The task data array
     * @return self A new Task instance
     */
    public static function fromArray(array $data): self
    {
        $task = new self($data['description'] ?? '');
        
        if (isset($data['agent'])) {
            $task->setAgent($data['agent']);
        }
        
        if (isset($data['tool_inputs'])) {
            foreach ($data['tool_inputs'] as $toolName => $input) {
                $task->setToolInput($toolName, $input);
            }
        }
        
        if (isset($data['context'])) {
            $task->setContext($data['context']);
        }
        
        if (isset($data['config'])) {
            $task->setConfig($data['config']);
        }
        
        return $task;
    }

    /**
     * Convert task to array.
     * 
     * @return array<string, mixed> The task data as an array
     */
    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'agent' => $this->agent,
            'tool_inputs' => $this->toolInputs,
            'context' => $this->context,
            'config' => $this->config,
        ];
    }

    /**
     * Enable streaming mode for this task.
     * 
     * @param callable|null $chunkCallback Optional callback for each chunk
     * @return self Returns the task instance for method chaining
     */
    public function stream(?callable $chunkCallback = null): self
    {
        $this->config['streaming'] = true;
        $this->config['chunk_callback'] = $chunkCallback;
        return $this;
    }

    /**
     * Check if streaming is enabled for this task.
     * 
     * @return bool True if streaming is enabled, false otherwise
     */
    public function isStreaming(): bool
    {
        return $this->config['streaming'] ?? false;
    }

    /**
     * Get the chunk callback for streaming.
     * 
     * @return callable|null The chunk callback if set, null otherwise
     */
    public function getChunkCallback(): ?callable
    {
        return $this->config['chunk_callback'] ?? null;
    }
}
