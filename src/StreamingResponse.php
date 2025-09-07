<?php

namespace LaraFlowAI;

use Illuminate\Support\Facades\Log;

/**
 * StreamingResponse class handles real-time streaming responses from AI agents.
 * 
 * This class provides a way to stream responses token-by-token for better UX
 * in chat interfaces and real-time applications. It supports buffering,
 * caching, and callback mechanisms for handling streaming data.
 * 
 * @package LaraFlowAI
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class StreamingResponse
{
    /**
     * The generator for streaming chunks.
     * 
     * @var \Generator
     */
    protected \Generator $stream;

    /**
     * The agent role that generated this response.
     * 
     * @var string
     */
    protected string $agentRole;

    /**
     * Tool results from the response.
     * 
     * @var array<string, mixed>
     */
    protected array $toolResults = [];

    /**
     * The complete response content (built from stream).
     * 
     * @var string
     */
    protected string $content = '';

    /**
     * Start time of the response generation.
     * 
     * @var float
     */
    protected float $startTime;

    /**
     * End time of the response generation.
     * 
     * @var float|null
     */
    protected ?float $endTime = null;

    /**
     * Whether the stream has been consumed.
     * 
     * @var bool
     */
    protected bool $consumed = false;

    /**
     * Callback function for handling each chunk.
     * 
     * @var callable|null
     */
    protected $chunkCallback = null;

    /**
     * Buffer for accumulating chunks.
     * 
     * @var string
     */
    protected string $buffer = '';

    /**
     * Buffer size for token optimization.
     * 
     * @var int
     */
    protected int $bufferSize = 10;

    /**
     * Create a new StreamingResponse instance.
     * 
     * @param \Generator $stream The streaming generator
     * @param string $agentRole The agent role
     * @param array<string, mixed> $toolResults Tool execution results
     * @param callable|null $chunkCallback Optional callback for each chunk
     */
    public function __construct(
        \Generator $stream,
        string $agentRole,
        array $toolResults = [],
        ?callable $chunkCallback = null
    ) {
        $this->stream = $stream;
        $this->agentRole = $agentRole;
        $this->toolResults = $toolResults;
        $this->chunkCallback = $chunkCallback;
        $this->startTime = microtime(true);
    }

    /**
     * Get the next chunk from the stream.
     * 
     * @return string|null The next chunk or null if stream is complete
     */
    public function getNextChunk(): ?string
    {
        if ($this->consumed) {
            return null;
        }

        try {
            if ($this->stream->valid()) {
                $chunk = $this->stream->current();
                $this->stream->next();
                
                if ($chunk !== null) {
                    $this->content .= $chunk;
                    $this->buffer .= $chunk;
                    
                    // Call chunk callback if provided
                    if ($this->chunkCallback) {
                        call_user_func($this->chunkCallback, $chunk, $this->content);
                    }
                    
                    // Process buffer if it reaches the buffer size
                    if (strlen($this->buffer) >= $this->bufferSize) {
                        $this->processBuffer();
                    }
                }
                
                return $chunk;
            } else {
                $this->finish();
                return null;
            }
        } catch (\Exception $e) {
            Log::error('LaraFlowAI: Streaming error', [
                'error' => $e->getMessage(),
                'agent_role' => $this->agentRole
            ]);
            $this->finish();
            return null;
        }
    }

    /**
     * Get all remaining chunks at once.
     * 
     * @return string The complete response content
     */
    public function getContent(): string
    {
        if (!$this->consumed) {
            $this->consumeAll();
        }
        
        return $this->content;
    }

    /**
     * Consume all remaining chunks.
     * 
     * @return void
     */
    public function consumeAll(): void
    {
        while ($this->getNextChunk() !== null) {
            // Continue consuming
        }
    }

    /**
     * Check if the stream has more chunks.
     * 
     * @return bool True if there are more chunks, false otherwise
     */
    public function hasMoreChunks(): bool
    {
        return !$this->consumed && $this->stream->valid();
    }

    /**
     * Get the current buffer content.
     * 
     * @return string The current buffer
     */
    public function getBuffer(): string
    {
        return $this->buffer;
    }

    /**
     * Clear the buffer.
     * 
     * @return void
     */
    public function clearBuffer(): void
    {
        $this->buffer = '';
    }

    /**
     * Set the buffer size for token optimization.
     * 
     * @param int $size The buffer size
     * @return self Returns the instance for method chaining
     */
    public function setBufferSize(int $size): self
    {
        $this->bufferSize = max(1, $size);
        return $this;
    }

    /**
     * Process the current buffer.
     * 
     * @return void
     */
    protected function processBuffer(): void
    {
        // This can be overridden for custom buffer processing
        // For now, we just clear the buffer
        $this->clearBuffer();
    }

    /**
     * Finish the streaming response.
     * 
     * @return void
     */
    protected function finish(): void
    {
        $this->consumed = true;
        $this->endTime = microtime(true);
        
        // Process any remaining buffer
        if (!empty($this->buffer)) {
            $this->processBuffer();
        }
        
        Log::info('LaraFlowAI: Streaming response completed', [
            'agent_role' => $this->agentRole,
            'content_length' => strlen($this->content),
            'execution_time' => $this->getExecutionTime()
        ]);
    }

    /**
     * Get the execution time in seconds.
     * 
     * @return float The execution time
     */
    public function getExecutionTime(): float
    {
        $endTime = $this->endTime ?? microtime(true);
        return $endTime - $this->startTime;
    }

    /**
     * Get the agent role.
     * 
     * @return string The agent role
     */
    public function getAgentRole(): string
    {
        return $this->agentRole;
    }

    /**
     * Get tool results.
     * 
     * @return array<string, mixed> The tool results
     */
    public function getToolResults(): array
    {
        return $this->toolResults;
    }

    /**
     * Check if the response is complete.
     * 
     * @return bool True if complete, false otherwise
     */
    public function isComplete(): bool
    {
        return $this->consumed;
    }

    /**
     * Get the current content length.
     * 
     * @return int The content length
     */
    public function getContentLength(): int
    {
        return strlen($this->content);
    }

    /**
     * Set a callback for handling chunks.
     * 
     * @param callable $callback The callback function
     * @return self Returns the instance for method chaining
     */
    public function onChunk(callable $callback): self
    {
        $this->chunkCallback = $callback;
        return $this;
    }

    /**
     * Convert the streaming response to a regular Response.
     * 
     * @return Response A regular Response instance
     */
    public function toResponse(): Response
    {
        $this->consumeAll();
        return new Response($this->content, $this->agentRole, $this->toolResults);
    }

    /**
     * Get response statistics.
     * 
     * @return array<string, mixed> Response statistics
     */
    public function getStats(): array
    {
        return [
            'agent_role' => $this->agentRole,
            'content_length' => strlen($this->content),
            'execution_time' => $this->getExecutionTime(),
            'is_complete' => $this->isComplete(),
            'buffer_size' => $this->bufferSize,
            'tool_results_count' => count($this->toolResults),
        ];
    }
}
