<?php

namespace LaraFlowAI\Contracts;

/**
 * ToolContract interface defines the contract for tools.
 * 
 * This interface ensures that all tools implement the same
 * basic functionality for execution, configuration, and
 * availability checking.
 * 
 * @package LaraFlowAI\Contracts
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
interface ToolContract
{
    /**
     * Get the tool name.
     * 
     * @return string The tool name
     */
    public function getName(): string;

    /**
     * Get the tool description.
     * 
     * @return string The tool description
     */
    public function getDescription(): string;

    /**
     * Execute the tool with given input.
     * 
     * @param array<string, mixed> $input The input data
     * @return mixed The execution result
     */
    public function run(array $input): mixed;

    /**
     * Get the tool's input schema.
     * 
     * @return array<string, mixed> The input schema
     */
    public function getInputSchema(): array;

    /**
     * Check if the tool is available.
     * 
     * @return bool True if tool is available, false otherwise
     */
    public function isAvailable(): bool;
}
