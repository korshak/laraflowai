<?php

namespace LaraFlowAI;

/**
 * FlowResult class represents the result of a flow execution.
 * 
 * A flow result contains information about the execution of all steps
 * in a flow, including success status, execution time, and detailed
 * results for each step.
 * 
 * @package LaraFlowAI
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class FlowResult
{
    /**
     * Array of step execution results.
     * 
     * @var array<int, array<string, mixed>>
     */
    protected array $results;

    /**
     * Total execution time in seconds.
     * 
     * @var float
     */
    protected float $executionTime;

    /**
     * Whether the flow execution was successful.
     * 
     * @var bool
     */
    protected bool $success;

    /**
     * Error message if execution failed.
     * 
     * @var string|null
     */
    protected ?string $error = null;

    /**
     * Create a new FlowResult instance.
     * 
     * @param array<int, array<string, mixed>> $results Array of step execution results
     * @param float $executionTime Total execution time in seconds
     * @param bool $success Whether the execution was successful
     * @param string|null $error Error message if execution failed
     */
    public function __construct(
        array $results,
        float $executionTime,
        bool $success = true,
        ?string $error = null
    ) {
        $this->results = $results;
        $this->executionTime = $executionTime;
        $this->success = $success;
        $this->error = $error;
    }

    /**
     * Get all results.
     * 
     * @return array<int, array<string, mixed>> Array of step execution results
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get result by step index.
     * 
     * @param int $stepIndex The index of the step
     * @return array<string, mixed>|null The step result if found, null otherwise
     */
    public function getResult(int $stepIndex): ?array
    {
        return $this->results[$stepIndex] ?? null;
    }

    /**
     * Get successful results only.
     * 
     * @return array<int, array<string, mixed>> Array of successful step results
     */
    public function getSuccessfulResults(): array
    {
        return array_filter($this->results, function ($result) {
            return $result['success'] ?? false;
        });
    }

    /**
     * Get failed results only.
     * 
     * @return array<int, array<string, mixed>> Array of failed step results
     */
    public function getFailedResults(): array
    {
        return array_filter($this->results, function ($result) {
            return !($result['success'] ?? false);
        });
    }

    /**
     * Get execution time.
     * 
     * @return float The execution time in seconds
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    /**
     * Check if execution was successful.
     * 
     * @return bool True if execution was successful, false otherwise
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if execution failed.
     * 
     * @return bool True if execution failed, false otherwise
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Get error message if any.
     * 
     * @return string|null The error message if execution failed, null otherwise
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Get total number of steps executed.
     * 
     * @return int The number of steps executed
     */
    public function getStepCount(): int
    {
        return count($this->results);
    }

    /**
     * Get successful step count.
     * 
     * @return int The number of successful steps
     */
    public function getSuccessfulStepCount(): int
    {
        return count($this->getSuccessfulResults());
    }

    /**
     * Get failed step count.
     * 
     * @return int The number of failed steps
     */
    public function getFailedStepCount(): int
    {
        return count($this->getFailedResults());
    }

    /**
     * Get summary of execution.
     * 
     * @return array<string, mixed> Summary of the flow execution
     */
    public function getSummary(): array
    {
        return [
            'success' => $this->success,
            'execution_time' => $this->executionTime,
            'total_steps' => $this->getStepCount(),
            'successful_steps' => $this->getSuccessfulStepCount(),
            'failed_steps' => $this->getFailedStepCount(),
            'error' => $this->error,
        ];
    }

    /**
     * Get results by step type.
     * 
     * @param string $type The step type to filter by
     * @return array<int, array<string, mixed>> Array of results for the specified type
     */
    public function getResultsByType(string $type): array
    {
        return array_filter($this->results, function ($result) use ($type) {
            return ($result['step']->getType() ?? '') === $type;
        });
    }

    /**
     * Get crew results.
     * 
     * @return array<int, array<string, mixed>> Array of crew step results
     */
    public function getCrewResults(): array
    {
        return $this->getResultsByType('crew');
    }

    /**
     * Get condition results.
     * 
     * @return array<int, array<string, mixed>> Array of condition step results
     */
    public function getConditionResults(): array
    {
        return $this->getResultsByType('condition');
    }

    /**
     * Convert to array.
     * 
     * @return array<string, mixed> The flow result as an array
     */
    public function toArray(): array
    {
        return [
            'results' => array_map(function ($result) {
                return [
                    'step_index' => $result['step_index'] ?? null,
                    'step_name' => $result['step']->getName() ?? null,
                    'step_type' => $result['step']->getType() ?? null,
                    'result' => $result['result'],
                    'execution_time' => $result['execution_time'] ?? 0,
                    'success' => $result['success'] ?? false,
                    'error' => $result['error'] ?? null,
                ];
            }, $this->results),
            'summary' => $this->getSummary(),
        ];
    }

    /**
     * Convert to JSON.
     * 
     * @param int $options JSON encoding options
     * @return string The flow result as JSON string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
