<?php

declare(strict_types=1);

namespace LaraFlowAI\MCP\Exceptions;

class MCPExecutionException extends MCPException
{
    public function __construct(
        string $message,
        public readonly ?int $errorCode = null,
        ?array $data = null,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $errorCode ?? 0, $previous, $data);
    }
}
