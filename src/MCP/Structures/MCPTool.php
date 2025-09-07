<?php

declare(strict_types=1);

namespace LaraFlowAI\MCP\Structures;

class MCPTool
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array $inputSchema = [],
        public readonly ?string $serverId = null
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'inputSchema' => $this->inputSchema,
        ];
    }

    public static function fromArray(array $data, ?string $serverId = null): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'],
            inputSchema: $data['inputSchema'] ?? [],
            serverId: $serverId
        );
    }
}
