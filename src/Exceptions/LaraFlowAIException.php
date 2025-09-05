<?php

namespace LaraFlowAI\Exceptions;

use Exception;

class LaraFlowAIException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create a provider not found exception.
     */
    public static function providerNotFound(string $provider): self
    {
        return new self("Provider '{$provider}' not found or not configured.");
    }

    /**
     * Create a tool not found exception.
     */
    public static function toolNotFound(string $tool): self
    {
        return new self("Tool '{$tool}' not found or not available.");
    }

    /**
     * Create a memory operation failed exception.
     */
    public static function memoryOperationFailed(string $operation): self
    {
        return new self("Memory operation '{$operation}' failed.");
    }

    /**
     * Create a crew execution failed exception.
     */
    public static function crewExecutionFailed(string $reason): self
    {
        return new self("Crew execution failed: {$reason}");
    }

    /**
     * Create a flow execution failed exception.
     */
    public static function flowExecutionFailed(string $reason): self
    {
        return new self("Flow execution failed: {$reason}");
    }

    /**
     * Create a validation failed exception.
     */
    public static function validationFailed(array $errors): self
    {
        $message = 'Validation failed: ' . implode(', ', $errors);
        return new self($message);
    }
}
