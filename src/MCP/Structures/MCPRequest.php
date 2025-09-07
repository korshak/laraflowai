<?php

declare(strict_types=1);

namespace LaraFlowAI\MCP\Structures;

class MCPRequest
{
    public function __construct(
        public readonly string $method,
        public readonly array $params = [],
        public readonly ?int $id = null
    ) {}

    public function toArray(): array
    {
        return [
            'jsonrpc' => '2.0',
            'method' => $this->method,
            'params' => $this->params,
            'id' => $this->id ?? uniqid()
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
