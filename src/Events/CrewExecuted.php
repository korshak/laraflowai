<?php

namespace LaraFlowAI\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaraFlowAI\CrewResult;

/**
 * CrewExecuted event is fired when a crew execution completes successfully.
 * 
 * This event contains the result of the crew execution and can be used
 * for logging, notifications, or other post-execution processing.
 * 
 * @package LaraFlowAI\Events
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class CrewExecuted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The crew execution result.
     * 
     * @var CrewResult
     */
    public CrewResult $result;

    /**
     * Create a new event instance.
     * 
     * @param CrewResult $result The crew execution result
     */
    public function __construct(CrewResult $result)
    {
        $this->result = $result;
    }
}
