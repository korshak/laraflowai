<?php

declare(strict_types=1);

namespace LaraFlowAI\MCP\Structures;

class MCPResponse
{
    public function __construct(
        public readonly array $data,
        public readonly ?int $id = null
    ) {}

    public function isError(): bool
    {
        return isset($this->data['error']);
    }

    public function getResult(): mixed
    {
        return $this->data['result'] ?? null;
    }

    public function getError(): ?array
    {
        return $this->data['error'] ?? null;
    }

    public function getErrorCode(): ?int
    {
        return $this->getError()['code'] ?? null;
    }

    public function getErrorMessage(): ?string
    {
        return $this->getError()['message'] ?? null;
    }

    public function getId(): ?int
    {
        return $this->id ?? $this->data['id'] ?? null;
    }

    public function getJsonRpcVersion(): string
    {
        return $this->data['jsonrpc'] ?? '2.0';
    }

    public function isSuccess(): bool
    {
        return !$this->isError() && isset($this->data['result']);
    }

    public static function fromArray(array $data): self
    {
        return new self($data, $data['id'] ?? null);
    }
}
