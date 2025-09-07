<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default LLM provider that will be used by
    | LaraFlowAI when no specific provider is requested.
    |
    */

    'default_provider' => env('LARAFLOWAI_DEFAULT_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | LLM Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the LLM providers for your application. Each
    | provider configuration includes the driver class and necessary
    | credentials and options.
    |
    */

    'providers' => [
        'openai' => [
            'driver' => \LaraFlowAI\Providers\OpenAIProvider::class,
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4'),
            'mode' => env('OPENAI_MODE', 'chat'),
            'timeout' => env('OPENAI_TIMEOUT', 60),
        ],

        'anthropic' => [
            'driver' => \LaraFlowAI\Providers\AnthropicProvider::class,
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
            'timeout' => env('ANTHROPIC_TIMEOUT', 60),
        ],

        'ollama' => [
            'driver' => \LaraFlowAI\Providers\OllamaProvider::class,
            'host' => env('OLLAMA_HOST', 'http://localhost:11434'),
            'model' => env('OLLAMA_MODEL', 'mistral'),
            'timeout' => env('OLLAMA_TIMEOUT', 60),
        ],

        'grok' => [
            'driver' => \LaraFlowAI\Providers\GrokProvider::class,
            'api_key' => env('GROK_API_KEY'),
            'model' => env('GROK_MODEL', 'grok-4'),
            'timeout' => env('GROK_TIMEOUT', 120),
        ],

        'gemini' => [
            'driver' => \LaraFlowAI\Providers\GeminiProvider::class,
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-pro'),
            'timeout' => env('GEMINI_TIMEOUT', 60),
        ],

        'deepseek' => [
            'driver' => \LaraFlowAI\Providers\DeepSeekProvider::class,
            'api_key' => env('DEEPSEEK_API_KEY'),
            'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            'mode' => env('DEEPSEEK_MODE', 'chat'),
            'timeout' => env('DEEPSEEK_TIMEOUT', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the memory system for storing agent context
    | and conversation history.
    |
    */

    'memory' => [
        'driver' => env('LARAFLOWAI_MEMORY_DRIVER', 'database'),
        'table' => env('LARAFLOWAI_MEMORY_TABLE', 'laraflowai_memory'),
        'cache_ttl' => env('LARAFLOWAI_MEMORY_CACHE_TTL', 3600),
        'cleanup_interval' => env('LARAFLOWAI_MEMORY_CLEANUP_INTERVAL', 86400),
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure default prompt settings for agents.
    | Prompts are built using simple string templating.
    |
    */

    'prompts' => [
        'include_memory' => true,
        'include_tools' => true,
        'max_context_length' => 2000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Agent Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure default settings for agents.
    |
    */

    'agent' => [
        'default_llm_options' => [
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ],
        'memory_search_limit' => 5,
        'context_window_size' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Crew Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure default settings for crews.
    |
    */

    'crew' => [
        'execution_mode' => env('LARAFLOWAI_CREW_EXECUTION_MODE', 'sequential'),
        'max_parallel_tasks' => env('LARAFLOWAI_MAX_PARALLEL_TASKS', 5),
        'timeout' => env('LARAFLOWAI_CREW_TIMEOUT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Flow Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure default settings for flows.
    |
    */

    'flow' => [
        'max_steps' => env('LARAFLOWAI_MAX_FLOW_STEPS', 50),
        'timeout' => env('LARAFLOWAI_FLOW_TIMEOUT', 600),
        'continue_on_error' => env('LARAFLOWAI_FLOW_CONTINUE_ON_ERROR', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure logging for LaraFlowAI operations.
    |
    */

    'logging' => [
        'enabled' => env('LARAFLOWAI_LOGGING_ENABLED', true),
        'level' => env('LARAFLOWAI_LOG_LEVEL', 'info'),
        'channels' => [
            'agent' => env('LARAFLOWAI_AGENT_LOG_CHANNEL', 'default'),
            'crew' => env('LARAFLOWAI_CREW_LOG_CHANNEL', 'default'),
            'flow' => env('LARAFLOWAI_FLOW_LOG_CHANNEL', 'default'),
            'memory' => env('LARAFLOWAI_MEMORY_LOG_CHANNEL', 'default'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure queue settings for async operations.
    |
    */

    'queue' => [
        'enabled' => env('LARAFLOWAI_QUEUE_ENABLED', false),
        'connection' => env('LARAFLOWAI_QUEUE_CONNECTION', 'default'),
        'queue' => env('LARAFLOWAI_QUEUE_NAME', 'laraflowai'),
        'retry_after' => env('LARAFLOWAI_QUEUE_RETRY_AFTER', 90),
        'tries' => env('LARAFLOWAI_QUEUE_TRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure cache settings for LaraFlowAI.
    |
    */

    'cache' => [
        'enabled' => env('LARAFLOWAI_CACHE_ENABLED', true),
        'driver' => env('LARAFLOWAI_CACHE_DRIVER', 'redis'),
        'prefix' => env('LARAFLOWAI_CACHE_PREFIX', 'laraflowai'),
        'ttl' => env('LARAFLOWAI_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Streaming Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure streaming settings for real-time responses.
    |
    */

    'streaming' => [
        'enabled' => env('LARAFLOWAI_STREAMING_ENABLED', true),
        'default_buffer_size' => env('LARAFLOWAI_STREAMING_BUFFER_SIZE', 10),
        'max_chunk_size' => env('LARAFLOWAI_STREAMING_MAX_CHUNK_SIZE', 1000),
        'timeout' => env('LARAFLOWAI_STREAMING_TIMEOUT', 300),
        'chunk_delay' => env('LARAFLOWAI_STREAMING_CHUNK_DELAY', 0), // milliseconds
        'enable_caching' => env('LARAFLOWAI_STREAMING_CACHE', true),
        'cache_ttl' => env('LARAFLOWAI_STREAMING_CACHE_TTL', 3600),
        
        // WebSocket configuration
        'websocket' => [
            'enabled' => env('LARAFLOWAI_WEBSOCKET_ENABLED', false),
            'driver' => env('LARAFLOWAI_WEBSOCKET_DRIVER', 'pusher'),
            'channel_prefix' => env('LARAFLOWAI_WEBSOCKET_CHANNEL_PREFIX', 'laraflowai'),
        ],
        
        // Server-Sent Events configuration
        'sse' => [
            'enabled' => env('LARAFLOWAI_SSE_ENABLED', true),
            'retry_timeout' => env('LARAFLOWAI_SSE_RETRY_TIMEOUT', 3000), // milliseconds
            'keep_alive_interval' => env('LARAFLOWAI_SSE_KEEP_ALIVE', 30), // seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP (Model Context Protocol) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure external MCP servers for extended functionality.
    | MCP servers provide additional tools and capabilities that can be
    | integrated into your AI workflows.
    |
    */

    'mcp' => [
        'enabled' => env('LARAFLOWAI_MCP_ENABLED', false),
        'default_timeout' => env('LARAFLOWAI_MCP_TIMEOUT', 30),
        'cache_tools_ttl' => env('LARAFLOWAI_MCP_CACHE_TTL', 3600),
        'retry_attempts' => env('LARAFLOWAI_MCP_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('LARAFLOWAI_MCP_RETRY_DELAY', 1000), // milliseconds
        
        'servers' => [
            // Example MCP server configuration
            'example_server' => [
                'name' => 'Example MCP Server',
                'url' => env('MCP_EXAMPLE_SERVER_URL', 'http://localhost:3000/api/mcp'),
                'auth_token' => env('MCP_EXAMPLE_SERVER_TOKEN'),
                'timeout' => env('MCP_EXAMPLE_SERVER_TIMEOUT', 30),
                'enabled' => env('MCP_EXAMPLE_SERVER_ENABLED', false),
                'available_actions' => [
                    'search_web',
                    'get_weather',
                    'send_email',
                    'create_calendar_event'
                ],
                'description' => 'Example MCP server with common tools',
                'version' => '1.0.0',
                'health_check_interval' => 300, // seconds
            ],
            
            // Add more MCP servers as needed
            // 'another_server' => [
            //     'name' => 'Another MCP Server',
            //     'url' => env('MCP_ANOTHER_SERVER_URL'),
            //     'auth_token' => env('MCP_ANOTHER_SERVER_TOKEN'),
            //     'timeout' => 60,
            //     'enabled' => true,
            //     'available_actions' => [],
            //     'description' => 'Another MCP server',
            //     'version' => '1.0.0',
            // ],
        ],
        
        'default_headers' => [
            'User-Agent' => 'LaraFlowAI/1.0.0',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
        
        'logging' => [
            'enabled' => env('LARAFLOWAI_MCP_LOGGING_ENABLED', true),
            'level' => env('LARAFLOWAI_MCP_LOG_LEVEL', 'info'),
            'log_requests' => env('LARAFLOWAI_MCP_LOG_REQUESTS', true),
            'log_responses' => env('LARAFLOWAI_MCP_LOG_RESPONSES', false),
        ],
    ],
];
