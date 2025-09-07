<?php

declare(strict_types=1);

namespace LaraFlowAI\MCP\Constants;

class MCPErrorCodes
{
    // JSON-RPC 2.0 standard errors
    public const PARSE_ERROR = -32700;
    public const INVALID_REQUEST = -32600;
    public const METHOD_NOT_FOUND = -32601;
    public const INVALID_PARAMS = -32602;
    public const INTERNAL_ERROR = -32603;

    // MCP specific errors
    public const INVALID_PROTOCOL_VERSION = -32001;
    public const INVALID_CAPABILITIES = -32002;
    public const SERVER_ERROR = -32003;
    public const TOOL_NOT_FOUND = -32004;
    public const RESOURCE_NOT_FOUND = -32005;
    public const PROMPT_NOT_FOUND = -32006;
    public const SAMPLE_NOT_FOUND = -32007;
    public const UNAUTHORIZED = -32008;
    public const FORBIDDEN = -32009;
    public const RATE_LIMITED = -32010;
    public const TIMEOUT = -32011;

    public static function getMessage(int $code): string
    {
        return match ($code) {
            self::PARSE_ERROR => 'Parse error',
            self::INVALID_REQUEST => 'Invalid Request',
            self::METHOD_NOT_FOUND => 'Method not found',
            self::INVALID_PARAMS => 'Invalid params',
            self::INTERNAL_ERROR => 'Internal error',
            self::INVALID_PROTOCOL_VERSION => 'Invalid protocol version',
            self::INVALID_CAPABILITIES => 'Invalid capabilities',
            self::SERVER_ERROR => 'Server error',
            self::TOOL_NOT_FOUND => 'Tool not found',
            self::RESOURCE_NOT_FOUND => 'Resource not found',
            self::PROMPT_NOT_FOUND => 'Prompt not found',
            self::SAMPLE_NOT_FOUND => 'Sample not found',
            self::UNAUTHORIZED => 'Unauthorized',
            self::FORBIDDEN => 'Forbidden',
            self::RATE_LIMITED => 'Rate limited',
            self::TIMEOUT => 'Timeout',
            default => 'Unknown error'
        };
    }
}
