<?php

declare(strict_types=1);

namespace LaraFlowAI\MCP\Exceptions;

use Exception;

abstract class MCPException extends Exception
{
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Exception $previous = null,
        public readonly ?array $data = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
