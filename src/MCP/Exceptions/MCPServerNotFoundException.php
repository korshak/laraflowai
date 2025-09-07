<?php

declare(strict_types=1);

namespace LaraFlowAI\MCP\Exceptions;

class MCPServerNotFoundException extends MCPException
{
    public function __construct(string $serverId, ?array $data = null)
    {
        parent::__construct(
            "MCP server '{$serverId}' not found or not enabled",
            404,
            null,
            $data
        );
    }
}
