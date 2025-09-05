<?php

namespace LaraFlowAI;

/**
 * FlowStep class represents a single step in a flow workflow.
 * 
 * A flow step can be of different types: crew, condition, delay, or custom.
 * Each step has a name, type, and configuration, and can have conditions
 * that determine whether it should be executed.
 * 
 * @package LaraFlowAI
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class FlowStep
{
    /**
     * The name of the step.
     * 
     * @var string
     */
    protected string $name;

    /**
     * The type of the step (crew, condition, delay, custom).
     * 
     * @var string
     */
    protected string $type;

    /**
     * The crew for crew-type steps.
     * 
     * @var Crew|null
     */
    protected ?Crew $crew = null;

    /**
     * The condition for condition-type steps.
     * 
     * @var FlowCondition|null
     */
    protected ?FlowCondition $condition = null;

    /**
     * The handler for custom-type steps.
     * 
     * @var callable|null
     */
    protected $handler = null;

    /**
     * Array of conditions for this step.
     * 
     * @var array<int, FlowCondition>
     */
    protected array $conditions = [];

    /**
     * Configuration options for this step.
     * 
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Create a new FlowStep instance.
     * 
     * @param string $name The name of the step
     * @param string $type The type of the step
     * @param array<string, mixed> $config Optional configuration array
     */
    public function __construct(string $name, string $type, array $config = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->config = $config;
    }

    /**
     * Create a crew step.
     * 
     * @param string $name The name of the step
     * @param Crew $crew The crew to execute
     * @param array<string, mixed> $config Optional configuration array
     * @return self A new FlowStep instance
     */
    public static function crew(string $name, Crew $crew, array $config = []): self
    {
        $step = new self($name, 'crew', $config);
        $step->crew = $crew;
        return $step;
    }

    /**
     * Create a condition step.
     * 
     * @param string $name The name of the step
     * @param FlowCondition $condition The condition to evaluate
     * @param array<string, mixed> $config Optional configuration array
     * @return self A new FlowStep instance
     */
    public static function condition(string $name, FlowCondition $condition, array $config = []): self
    {
        $step = new self($name, 'condition', $config);
        $step->condition = $condition;
        return $step;
    }

    /**
     * Create a delay step.
     * 
     * @param string $name The name of the step
     * @param int $seconds The number of seconds to delay
     * @param array<string, mixed> $config Optional configuration array
     * @return self A new FlowStep instance
     */
    public static function delay(string $name, int $seconds, array $config = []): self
    {
        $config['delay'] = $seconds;
        return new self($name, 'delay', $config);
    }

    /**
     * Create a custom step.
     * 
     * @param string $name The name of the step
     * @param callable $handler The custom handler function
     * @param array<string, mixed> $config Optional configuration array
     * @return self A new FlowStep instance
     */
    public static function custom(string $name, callable $handler, array $config = []): self
    {
        $step = new self($name, 'custom', $config);
        $step->handler = $handler;
        return $step;
    }

    /**
     * Add a condition to this step.
     * 
     * @param FlowCondition $condition The condition to add
     * @return self Returns the step instance for method chaining
     */
    public function addCondition(FlowCondition $condition): self
    {
        $this->conditions[] = $condition;
        return $this;
    }

    /**
     * Get step name.
     * 
     * @return string The step name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get step type.
     * 
     * @return string The step type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get crew for crew steps.
     * 
     * @return Crew|null The crew if this is a crew step, null otherwise
     */
    public function getCrew(): ?Crew
    {
        return $this->crew;
    }

    /**
     * Get condition for condition steps.
     * 
     * @return FlowCondition|null The condition if this is a condition step, null otherwise
     */
    public function getCondition(): ?FlowCondition
    {
        return $this->condition;
    }

    /**
     * Get handler for custom steps.
     * 
     * @return callable|null The handler if this is a custom step, null otherwise
     */
    public function getHandler(): ?callable
    {
        return $this->handler;
    }

    /**
     * Get conditions for this step.
     * 
     * @return array<int, FlowCondition> Array of conditions
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Get step configuration.
     * 
     * @return array<string, mixed> The step configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set step configuration.
     * 
     * @param array<string, mixed> $config The configuration to set
     * @return self Returns the step instance for method chaining
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Create a step from array data.
     * 
     * @param array<string, mixed> $data The step data array
     * @return self A new FlowStep instance
     */
    public static function fromArray(array $data): self
    {
        $step = new self($data['name'], $data['type'], $data['config'] ?? []);
        
        if (isset($data['crew']) && $data['crew'] instanceof Crew) {
            $step->crew = $data['crew'];
        }
        
        if (isset($data['condition']) && $data['condition'] instanceof FlowCondition) {
            $step->condition = $data['condition'];
        }
        
        if (isset($data['handler']) && is_callable($data['handler'])) {
            $step->handler = $data['handler'];
        }
        
        if (isset($data['conditions'])) {
            foreach ($data['conditions'] as $condition) {
                if ($condition instanceof FlowCondition) {
                    $step->addCondition($condition);
                }
            }
        }
        
        return $step;
    }

    /**
     * Convert step to array.
     * 
     * @return array<string, mixed> The step data as an array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'crew' => $this->crew ? get_class($this->crew) : null,
            'condition' => $this->condition ? get_class($this->condition) : null,
            'handler' => $this->handler ? 'callable' : null,
            'conditions' => array_map(fn($c) => get_class($c), $this->conditions),
            'config' => $this->config,
        ];
    }
}
