<?php

namespace LaraFlowAI\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * CrewExecutionFailed event is fired when a crew execution fails.
 * 
 * This event contains the exception that caused the failure and can be used
 * for error logging, notifications, or other error handling processing.
 * 
 * @package LaraFlowAI\Events
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class CrewExecutionFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The exception that caused the crew execution to fail.
     * 
     * @var Throwable
     */
    public Throwable $exception;

    /**
     * Create a new event instance.
     * 
     * @param Throwable $exception The exception that caused the failure
     */
    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }
}
