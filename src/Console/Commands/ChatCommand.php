<?php

namespace LaraFlowAI\Console\Commands;

use Illuminate\Console\Command;
use LaraFlowAI\Facades\FlowAI;
use LaraFlowAI\Agent;
use LaraFlowAI\Crew;
use LaraFlowAI\Flow;

class ChatCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'laraflowai:chat 
                            {--agent= : Use a specific agent class}
                            {--crew= : Use a specific crew class}
                            {--flow= : Use a specific flow class}
                            {--provider= : Set the LLM provider (openai, anthropic, etc.)}
                            {--model= : Set the specific model to use}
                            {--memory= : Enable memory (true/false)}
                            {--stream : Enable streaming responses}';

    /**
     * The console command description.
     */
    protected $description = 'Start an interactive chat session with LaraFlowAI';

    /**
     * The AI instance being used for chat.
     */
    protected $aiInstance = null;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayWelcomeMessage();
        
        // Setup AI instance
        if (!$this->setupAIInstance()) {
            return Command::FAILURE;
        }

        $this->displayUsageInstructions();
        $this->startChatLoop();

        return Command::SUCCESS;
    }

    /**
     * Display welcome message.
     */
    protected function displayWelcomeMessage(): void
    {
        $this->newLine();
        $this->info('🤖 Welcome to LaraFlowAI Interactive Chat!');
        $this->line('Type your messages and press Enter to chat with the AI.');
        $this->line('Type "exit", "quit", or "bye" to end the session.');
        $this->line('Type "help" for available commands.');
        $this->newLine();
    }

    /**
     * Setup the AI instance based on options.
     */
    protected function setupAIInstance(): bool
    {
        $agent = $this->option('agent');
        $crew = $this->option('crew');
        $flow = $this->option('flow');
        $provider = $this->option('provider');
        $model = $this->option('model');
        $memory = $this->option('memory');
        $stream = $this->option('stream');

        try {
            if ($agent) {
                $this->setupAgent($agent, $provider, $model, $memory);
            } elseif ($crew) {
                $this->setupCrew($crew, $provider, $model, $memory);
            } elseif ($flow) {
                $this->setupFlow($flow, $provider, $model, $memory);
            } else {
                $this->setupDefaultAgent($provider, $model, $memory);
            }

            $this->aiInstance = $this->aiInstance ?? FlowAI::agent();
            return true;
        } catch (\Exception $e) {
            $this->error("Failed to setup AI instance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Setup agent instance.
     */
    protected function setupAgent(string $agentClass, ?string $provider, ?string $model, ?bool $memory): void
    {
        $fullClassName = "\\App\\LaraFlowAI\\Agents\\{$agentClass}";
        
        if (!class_exists($fullClassName)) {
            throw new \Exception("Agent class {$fullClassName} not found. Run 'php artisan laraflowai:make:agent {$agentClass}' first.");
        }

        $this->aiInstance = new $fullClassName();
        
        if ($provider) {
            $this->aiInstance->provider($provider);
        }
        
        if ($model) {
            $this->aiInstance->model($model);
        }
        
        if ($memory !== null) {
            $this->aiInstance->memory($memory);
        }

        $this->info("Using agent: {$agentClass}");
    }

    /**
     * Setup crew instance.
     */
    protected function setupCrew(string $crewClass, ?string $provider, ?string $model, ?bool $memory): void
    {
        $fullClassName = "\\App\\LaraFlowAI\\Crews\\{$crewClass}";
        
        if (!class_exists($fullClassName)) {
            throw new \Exception("Crew class {$fullClassName} not found. Run 'php artisan laraflowai:make:crew {$crewClass}' first.");
        }

        $crew = new $fullClassName();
        $this->aiInstance = FlowAI::crew($crew);
        
        if ($provider) {
            $this->aiInstance->provider($provider);
        }
        
        if ($model) {
            $this->aiInstance->model($model);
        }
        
        if ($memory !== null) {
            $this->aiInstance->memory($memory);
        }

        $this->info("Using crew: {$crewClass}");
    }

    /**
     * Setup flow instance.
     */
    protected function setupFlow(string $flowClass, ?string $provider, ?string $model, ?bool $memory): void
    {
        $fullClassName = "\\App\\LaraFlowAI\\Flows\\{$flowClass}";
        
        if (!class_exists($fullClassName)) {
            throw new \Exception("Flow class {$fullClassName} not found. Run 'php artisan laraflowai:make:flow {$flowClass}' first.");
        }

        $flow = new $fullClassName();
        $this->aiInstance = FlowAI::flow($flow);
        
        if ($provider) {
            $this->aiInstance->provider($provider);
        }
        
        if ($model) {
            $this->aiInstance->model($model);
        }
        
        if ($memory !== null) {
            $this->aiInstance->memory($memory);
        }

        $this->info("Using flow: {$flowClass}");
    }

    /**
     * Setup default agent.
     */
    protected function setupDefaultAgent(?string $provider, ?string $model, ?bool $memory): void
    {
        $agent = new Agent(
            role: 'assistant',
            goal: 'Help users with their questions and tasks',
            memory: $memory ?? true
        );

        $this->aiInstance = FlowAI::agent($agent);
        
        if ($provider) {
            $this->aiInstance->provider($provider);
        }
        
        if ($model) {
            $this->aiInstance->model($model);
        }

        $this->info("Using default agent");
    }

    /**
     * Display usage instructions.
     */
    protected function displayUsageInstructions(): void
    {
        $this->line('Available commands:');
        $this->line('  help     - Show this help message');
        $this->line('  clear    - Clear the conversation history');
        $this->line('  status   - Show current AI configuration');
        $this->line('  exit/quit/bye - End the chat session');
        $this->newLine();
    }

    /**
     * Start the chat loop.
     */
    protected function startChatLoop(): void
    {
        $conversationHistory = [];

        while (true) {
            $input = $this->ask('You');

            if (empty(trim($input))) {
                continue;
            }

            $input = trim($input);

            // Handle special commands
            if (in_array(strtolower($input), ['exit', 'quit', 'bye'])) {
                $this->info('Goodbye! 👋');
                break;
            }

            if (strtolower($input) === 'help') {
                $this->displayUsageInstructions();
                continue;
            }

            if (strtolower($input) === 'clear') {
                $conversationHistory = [];
                $this->info('Conversation history cleared.');
                continue;
            }

            if (strtolower($input) === 'status') {
                $this->displayStatus();
                continue;
            }

            // Process the message
            try {
                $this->processMessage($input, $conversationHistory);
            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
            }
        }
    }

    /**
     * Process a user message.
     */
    protected function processMessage(string $input, array &$conversationHistory): void
    {
        $this->newLine();
        $this->info('AI:');

        if ($this->option('stream')) {
            $this->processStreamingResponse($input, $conversationHistory);
        } else {
            $this->processRegularResponse($input, $conversationHistory);
        }

        $this->newLine();
    }

    /**
     * Process a regular (non-streaming) response.
     */
    protected function processRegularResponse(string $input, array &$conversationHistory): void
    {
        $response = $this->aiInstance->execute($input);
        
        if (is_string($response)) {
            $this->line($response);
            $conversationHistory[] = ['user' => $input, 'ai' => $response];
        } else {
            $this->line('Response received (non-string format)');
            $conversationHistory[] = ['user' => $input, 'ai' => 'Response received'];
        }
    }

    /**
     * Process a streaming response.
     */
    protected function processStreamingResponse(string $input, array &$conversationHistory): void
    {
        $fullResponse = '';
        
        $task = FlowAI::task($input);
        $streamingResponse = $this->aiInstance->stream($task, function ($chunk) use (&$fullResponse) {
            $this->output->write($chunk);
            $fullResponse .= $chunk;
        });

        $conversationHistory[] = ['user' => $input, 'ai' => $fullResponse];
    }

    /**
     * Display current status.
     */
    protected function displayStatus(): void
    {
        $this->newLine();
        $this->info('Current Configuration:');
        
        $provider = $this->option('provider') ?: 'default';
        $model = $this->option('model') ?: 'default';
        $memory = $this->option('memory') ?: 'enabled';
        $stream = $this->option('stream') ? 'enabled' : 'disabled';
        
        $this->line("  Provider: {$provider}");
        $this->line("  Model: {$model}");
        $this->line("  Memory: {$memory}");
        $this->line("  Streaming: {$stream}");
        
        if ($this->option('agent')) {
            $this->line("  Agent: " . $this->option('agent'));
        } elseif ($this->option('crew')) {
            $this->line("  Crew: " . $this->option('crew'));
        } elseif ($this->option('flow')) {
            $this->line("  Flow: " . $this->option('flow'));
        } else {
            $this->line("  Mode: Default Agent");
        }
        
        $this->newLine();
    }
}
