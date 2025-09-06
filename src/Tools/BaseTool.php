<?php

namespace LaraFlowAI\Tools;

use LaraFlowAI\Contracts\ToolContract;
use Illuminate\Support\Facades\Log;

/**
 * BaseTool abstract class provides common functionality for all tools.
 * 
 * This class implements the ToolContract interface and provides a foundation
 * for all specific tool implementations. It handles common functionality
 * like input validation, logging, and configuration management.
 * 
 * @package LaraFlowAI\Tools
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
abstract class BaseTool implements ToolContract
{
    /**
     * The name of the tool.
     * 
     * @var string
     */
    protected string $name;

    /**
     * The description of the tool.
     * 
     * @var string
     */
    protected string $description;

    /**
     * Configuration options for the tool.
     * 
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Create a new BaseTool instance.
     * 
     * @param string $name The tool name
     * @param string $description The tool description
     * @param array<string, mixed> $config Optional configuration array
     */
    public function __construct(string $name, string $description, array $config = [])
    {
        $this->name = $name;
        $this->description = $description;
        $this->config = $config;
    }

    /**
     * Get the tool name.
     * 
     * @return string The tool name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the tool description.
     * 
     * @return string The tool description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the input schema for the tool.
     * 
     * @return array<string, mixed> The input schema
     */
    public function getInputSchema(): array
    {
        return $this->config['input_schema'] ?? [];
    }

    /**
     * Check if the tool is available.
     * 
     * @return bool True if tool is available, false otherwise
     */
    public function isAvailable(): bool
    {
        return $this->config['available'] ?? true;
    }

    /**
     * Validate input against schema.
     * 
     * @param array<string, mixed> $input The input data to validate
     * @return bool True if input is valid, false otherwise
     */
    protected function validateInput(array $input): bool
    {
        $schema = $this->getInputSchema();
        
        if (empty($schema)) {
            return true;
        }

        foreach ($schema as $field => $rules) {
            if (isset($rules['required']) && $rules['required'] && !isset($input[$field])) {
                Log::error('LaraFlowAI: Tool input validation failed', [
                    'tool' => $this->name,
                    'field' => $field,
                    'reason' => 'required field missing'
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Log tool execution.
     * 
     * @param array<string, mixed> $input The input data
     * @param mixed $result The execution result
     */
    protected function logExecution(array $input, mixed $result): void
    {
        Log::debug('LaraFlowAI: Tool executed', [
            'tool' => $this->name,
            'input' => $input,
            'result_type' => gettype($result),
            'result_length' => is_string($result) ? strlen($result) : null,
        ]);
    }
}
