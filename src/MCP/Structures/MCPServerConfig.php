<?php

declare(strict_types=1);

namespace LaraFlowAI\MCP\Structures;

class MCPServerConfig
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $url,
        public readonly bool $enabled = true,
        public readonly int $timeout = 30,
        public readonly ?string $authToken = null,
        public readonly string $authType = 'bearer',
        public readonly array $headers = [],
        public readonly array $capabilities = [],
        public readonly ?string $version = null
    ) {}

    public function getAuthHeaders(): array
    {
        if (!$this->authToken) {
            return [];
        }

        return match ($this->authType) {
            'bearer' => ['Authorization' => "Bearer {$this->authToken}"],
            'api_key' => ['X-API-Key' => $this->authToken],
            'basic' => ['Authorization' => "Basic " . base64_encode($this->authToken)],
            default => ['Authorization' => $this->authToken]
        };
    }

    public function getAllHeaders(): array
    {
        return array_merge(
            $this->headers,
            $this->getAuthHeaders(),
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        );
    }

    public static function fromArray(string $id, array $config): self
    {
        return new self(
            id: $id,
            name: $config['name'] ?? $id,
            url: $config['url'],
            enabled: $config['enabled'] ?? true,
            timeout: $config['timeout'] ?? 30,
            authToken: $config['auth_token'] ?? null,
            authType: $config['auth_type'] ?? 'bearer',
            headers: $config['headers'] ?? [],
            capabilities: $config['capabilities'] ?? [],
            version: $config['version'] ?? null
        );
    }
}
