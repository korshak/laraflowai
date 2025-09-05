<?php

namespace LaraFlowAI\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaraFlowAI\FlowResult;

/**
 * FlowExecuted event is fired when a flow execution completes successfully.
 * 
 * This event contains the result of the flow execution and can be used
 * for logging, notifications, or other post-execution processing.
 * 
 * @package LaraFlowAI\Events
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class FlowExecuted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The flow execution result.
     * 
     * @var FlowResult
     */
    public FlowResult $result;

    /**
     * Create a new event instance.
     * 
     * @param FlowResult $result The flow execution result
     */
    public function __construct(FlowResult $result)
    {
        $this->result = $result;
    }
}
