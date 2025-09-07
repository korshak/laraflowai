<?php

declare(strict_types=1);

namespace LaraFlowAI\MCP\Structures;

class MCPResource
{
    public function __construct(
        public readonly string $uri,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly ?string $mimeType = null,
        public readonly ?string $serverId = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'uri' => $this->uri,
            'name' => $this->name,
            'description' => $this->description,
            'mimeType' => $this->mimeType,
        ], fn($value) => $value !== null);
    }

    public static function fromArray(array $data, ?string $serverId = null): self
    {
        return new self(
            uri: $data['uri'],
            name: $data['name'],
            description: $data['description'] ?? null,
            mimeType: $data['mimeType'] ?? null,
            serverId: $serverId
        );
    }
}
