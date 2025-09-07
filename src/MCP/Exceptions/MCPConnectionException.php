<?php

declare(strict_types=1);

namespace LaraFlowAI\MCP\Exceptions;

class MCPConnectionException extends MCPException
{
    public function __construct(string $message, ?array $data = null, ?\Exception $previous = null)
    {
        parent::__construct($message, 0, $previous, $data);
    }
}
