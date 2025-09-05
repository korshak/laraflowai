<?php

namespace LaraFlowAI;

/**
 * CrewResult class represents the result of a crew execution.
 * 
 * A crew result contains information about the execution of all tasks
 * in a crew, including success status, execution time, and detailed
 * results for each task.
 * 
 * @package LaraFlowAI
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class CrewResult
{
    /**
     * Array of task execution results.
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
     * Whether the crew execution was successful.
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
     * Create a new CrewResult instance.
     * 
     * @param array<int, array<string, mixed>> $results Array of task execution results
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
     * @return array<int, array<string, mixed>> Array of task execution results
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get result by task index.
     * 
     * @param int $taskIndex The index of the task
     * @return array<string, mixed>|null The task result if found, null otherwise
     */
    public function getResult(int $taskIndex): ?array
    {
        return $this->results[$taskIndex] ?? null;
    }

    /**
     * Get all responses.
     * 
     * @return array<int, Response|null> Array of responses from all tasks
     */
    public function getResponses(): array
    {
        return array_map(function ($result) {
            return $result['response'] ?? null;
        }, $this->results);
    }

    /**
     * Get response by task index.
     * 
     * @param int $taskIndex The index of the task
     * @return Response|null The response if found, null otherwise
     */
    public function getResponse(int $taskIndex): ?Response
    {
        $result = $this->getResult($taskIndex);
        return $result['response'] ?? null;
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
     * Get total number of tasks executed.
     * 
     * @return int The number of tasks executed
     */
    public function getTaskCount(): int
    {
        return count($this->results);
    }

    /**
     * Get successful task count.
     * 
     * @return int The number of successful tasks
     */
    public function getSuccessfulTaskCount(): int
    {
        return count(array_filter($this->results, function ($result) {
            return isset($result['response']) && $result['response'] instanceof Response;
        }));
    }

    /**
     * Get failed task count.
     * 
     * @return int The number of failed tasks
     */
    public function getFailedTaskCount(): int
    {
        return $this->getTaskCount() - $this->getSuccessfulTaskCount();
    }

    /**
     * Get summary of execution.
     * 
     * @return array<string, mixed> Summary of the crew execution
     */
    public function getSummary(): array
    {
        return [
            'success' => $this->success,
            'execution_time' => $this->executionTime,
            'total_tasks' => $this->getTaskCount(),
            'successful_tasks' => $this->getSuccessfulTaskCount(),
            'failed_tasks' => $this->getFailedTaskCount(),
            'error' => $this->error,
        ];
    }

    /**
     * Convert to array.
     * 
     * @return array<string, mixed> The crew result as an array
     */
    public function toArray(): array
    {
        return [
            'results' => array_map(function ($result) {
                return [
                    'task_index' => $result['task_index'] ?? null,
                    'agent' => $result['agent'] ?? null,
                    'response' => $result['response'] ? $result['response']->toArray() : null,
                    'execution_time' => $result['execution_time'] ?? 0,
                ];
            }, $this->results),
            'summary' => $this->getSummary(),
        ];
    }

    /**
     * Convert to JSON.
     * 
     * @param int $options JSON encoding options
     * @return string The crew result as JSON string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
