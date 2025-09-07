<?php

/**
 * LaraFlowAI Livewire Streaming Example
 * 
 * This file demonstrates how to integrate LaraFlowAI streaming
 * with Laravel Livewire for real-time chat interfaces.
 */

namespace App\Livewire;

use Livewire\Component;
use LaraFlowAI\Facades\FlowAI;
use LaraFlowAI\StreamingResponse;

class ChatComponent extends Component
{
    public $messages = [];
    public $currentMessage = '';
    public $isStreaming = false;
    public $streamingContent = '';
    public $agent;

    protected $rules = [
        'currentMessage' => 'required|string|max:1000',
    ];

    public function mount()
    {
        // Initialize the AI agent
        $this->agent = FlowAI::agent(
            role: 'Chat Assistant',
            goal: 'Provide helpful and engaging responses in a chat format',
            provider: 'openai'
        );
        
        // Add welcome message
        $this->messages[] = [
            'type' => 'assistant',
            'content' => 'Hello! I\'m your AI assistant. How can I help you today?',
            'timestamp' => now()->toISOString(),
        ];
    }

    public function sendMessage()
    {
        $this->validate();

        // Add user message
        $this->messages[] = [
            'type' => 'user',
            'content' => $this->currentMessage,
            'timestamp' => now()->toISOString(),
        ];

        // Create task for the agent
        $task = FlowAI::task($this->currentMessage);
        
        // Clear current message
        $this->currentMessage = '';
        
        // Start streaming
        $this->startStreaming($task);
    }

    public function startStreaming($task)
    {
        $this->isStreaming = true;
        $this->streamingContent = '';
        
        // Add placeholder message for streaming
        $this->messages[] = [
            'type' => 'assistant',
            'content' => '',
            'timestamp' => now()->toISOString(),
            'is_streaming' => true,
        ];

        // Start streaming in the background
        $this->dispatch('start-streaming', [
            'task_description' => $task->getDescription(),
        ]);
    }

    public function handleStreamingChunk($chunk)
    {
        if ($this->isStreaming) {
            $this->streamingContent .= $chunk;
            
            // Update the last message with streaming content
            $lastIndex = count($this->messages) - 1;
            $this->messages[$lastIndex]['content'] = $this->streamingContent;
            
            // Emit event to update the frontend
            $this->dispatch('chunk-received', [
                'chunk' => $chunk,
                'full_content' => $this->streamingContent,
            ]);
        }
    }

    public function finishStreaming()
    {
        $this->isStreaming = false;
        
        // Mark the last message as complete
        $lastIndex = count($this->messages) - 1;
        $this->messages[$lastIndex]['is_streaming'] = false;
        
        // Clear streaming content
        $this->streamingContent = '';
        
        // Emit completion event
        $this->dispatch('streaming-complete');
    }

    public function render()
    {
        return view('livewire.chat-component');
    }
}

/**
 * JavaScript for handling streaming in the frontend
 */
?>

<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('start-streaming', (data) => {
        // Start Server-Sent Events connection for streaming
        const eventSource = new EventSource(`/api/stream-chat?task=${encodeURIComponent(data.task_description)}`);
        
        eventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            
            if (data.type === 'chunk') {
                // Handle streaming chunk
                Livewire.dispatch('handleStreamingChunk', { chunk: data.chunk });
            } else if (data.type === 'complete') {
                // Handle completion
                eventSource.close();
                Livewire.dispatch('finishStreaming');
            }
        };
        
        eventSource.onerror = function(event) {
            console.error('Streaming error:', event);
            eventSource.close();
            Livewire.dispatch('finishStreaming');
        };
    });
});
</script>

<?php

/**
 * API Route for handling streaming
 * Add this to your routes/api.php file
 */

use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use LaraFlowAI\Facades\FlowAI;

Route::get('/stream-chat', function (Request $request) {
    $taskDescription = $request->query('task');
    
    if (!$taskDescription) {
        return response()->json(['error' => 'Task description required'], 400);
    }

    // Create agent and task
    $agent = FlowAI::agent(
        role: 'Chat Assistant',
        goal: 'Provide helpful and engaging responses in a chat format',
        provider: 'openai'
    );
    
    $task = FlowAI::task($taskDescription);

    return new StreamedResponse(function () use ($agent, $task) {
        // Set headers for Server-Sent Events
        echo "data: " . json_encode(['type' => 'start']) . "\n\n";
        
        // Start streaming
        $streamingResponse = $agent->stream($task);
        
        while ($streamingResponse->hasMoreChunks()) {
            $chunk = $streamingResponse->getNextChunk();
            
            if ($chunk !== null) {
                // Send chunk to client
                echo "data: " . json_encode([
                    'type' => 'chunk',
                    'chunk' => $chunk
                ]) . "\n\n";
                
                // Flush output to client
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
        }
        
        // Send completion signal
        echo "data: " . json_encode(['type' => 'complete']) . "\n\n";
        
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'Connection' => 'keep-alive',
        'X-Accel-Buffering' => 'no', // Disable Nginx buffering
    ]);
});

/**
 * Alternative: WebSocket implementation using Pusher
 */

use Pusher\Pusher;

Route::post('/stream-chat-websocket', function (Request $request) {
    $taskDescription = $request->input('task');
    $channel = $request->input('channel', 'chat-stream');
    
    if (!$taskDescription) {
        return response()->json(['error' => 'Task description required'], 400);
    }

    // Create agent and task
    $agent = FlowAI::agent(
        role: 'Chat Assistant',
        goal: 'Provide helpful and engaging responses in a chat format',
        provider: 'openai'
    );
    
    $task = FlowAI::task($taskDescription);

    // Initialize Pusher
    $pusher = new Pusher(
        config('broadcasting.connections.pusher.key'),
        config('broadcasting.connections.pusher.secret'),
        config('broadcasting.connections.pusher.app_id'),
        config('broadcasting.connections.pusher.options')
    );

    // Start streaming in background
    dispatch(function () use ($agent, $task, $pusher, $channel) {
        $streamingResponse = $agent->stream($task);
        
        while ($streamingResponse->hasMoreChunks()) {
            $chunk = $streamingResponse->getNextChunk();
            
            if ($chunk !== null) {
                // Send chunk via WebSocket
                $pusher->trigger($channel, 'chunk', [
                    'chunk' => $chunk,
                    'timestamp' => now()->toISOString(),
                ]);
            }
        }
        
        // Send completion signal
        $pusher->trigger($channel, 'complete', [
            'message' => 'Streaming completed',
            'timestamp' => now()->toISOString(),
        ]);
    });

    return response()->json(['status' => 'streaming_started', 'channel' => $channel]);
});

/**
 * Frontend JavaScript for WebSocket streaming
 */
?>

<script>
// WebSocket streaming with Pusher
const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
    cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}'
});

const channel = pusher.subscribe('chat-stream');

channel.bind('chunk', function(data) {
    // Handle streaming chunk
    Livewire.dispatch('handleStreamingChunk', { chunk: data.chunk });
});

channel.bind('complete', function(data) {
    // Handle completion
    Livewire.dispatch('finishStreaming');
});

// Start streaming
function startStreaming(taskDescription) {
    fetch('/api/stream-chat-websocket', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            task: taskDescription,
            channel: 'chat-stream'
        })
    });
}
</script>
