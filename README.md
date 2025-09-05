# LaraFlowAI

[![Latest Version](https://img.shields.io/badge/version-alpha2-blue.svg)](https://packagist.org/packages/laraflowai/laraflowai)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-purple.svg)](https://php.net)
[![MCP Support](https://img.shields.io/badge/MCP-Enabled-orange.svg)](https://modelcontextprotocol.io)

A powerful Laravel extension for building multi-agent AI workflows inspired by crewAI. LaraFlowAI provides a comprehensive framework for creating intelligent agents, crews, and flows within your Laravel application, supporting multiple AI providers, MCP servers, and advanced workflow management.

## ✨ Features

- 🤖 **Multi-Agent System**: Create intelligent agents with specific roles, goals, and capabilities
- 👥 **Crew Management**: Organize agents into collaborative teams for complex task execution
- 🔄 **Flow Control**: Build sophisticated workflows with conditional logic and branching
- 🧠 **Memory System**: Short-term and long-term memory with intelligent recall and search
- 🔌 **Multi-Provider Support**: OpenAI, Anthropic, Grok, Gemini, DeepSeek, and Ollama integration
- 🎯 **Multi-Mode Support**: Chat, completion, and embedding modes for different use cases
- 🛠️ **Extensible Tools**: HTTP, Database, Filesystem, MCP servers, and custom tool implementations
- 🔗 **MCP Integration**: Full support for Model Context Protocol servers for external tool integration
- 📝 **Smart Templates**: Built-in prompt templating with context-aware generation
- ⚡ **Queue Integration**: Asynchronous execution with Laravel queues and Horizon
- 📊 **Observability**: Comprehensive logging, monitoring, and performance analytics
- 🔒 **Security**: Rate limiting, authentication, and input validation
- 🧪 **Testing**: Built-in testing utilities and comprehensive test coverage
- 📚 **Documentation**: Extensive guides, examples, and API documentation

## 🚀 Installation

### Requirements

- PHP 8.2 or higher
- Laravel 10.x or 11.x
- Composer

### Quick Install

```bash
# Install the package
composer require laraflowai/laraflowai

# Publish configuration and migrations
php artisan vendor:publish --provider="LaraFlowAI\LaraFlowAIServiceProvider"

# Run migrations
php artisan migrate
```

### Automated Installation

For a complete setup with all dependencies:

```bash
# Download and run the installation script
curl -sSL https://raw.githubusercontent.com/laraflowai/laraflowai/main/install-laravel.sh | bash
```

### Manual Setup

1. **Install via Composer:**
   ```bash
   composer require laraflowai/laraflowai
   ```

2. **Publish Configuration:**
   ```bash
   php artisan vendor:publish --provider="LaraFlowAI\LaraFlowAIServiceProvider"
   ```

3. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

4. **Configure Environment:**
   ```bash
   # Add to your .env file
   OPENAI_API_KEY=your_openai_api_key
   GROQ_API_KEY=your_groq_api_key
   GEMINI_API_KEY=your_gemini_api_key
   LARAFLOWAI_DEFAULT_PROVIDER=openai
   ```

## ⚙️ Configuration

### Environment Variables

Add your API keys to your `.env` file:

```env
# AI Provider API Keys
OPENAI_API_KEY=your_openai_api_key
ANTHROPIC_API_KEY=your_anthropic_api_key
GROQ_API_KEY=your_groq_api_key
GEMINI_API_KEY=your_gemini_api_key
DEEPSEEK_API_KEY=your_deepseek_api_key

# Provider Modes (optional, defaults to 'chat')
OPENAI_MODE=chat
DEEPSEEK_MODE=chat

# Local AI (Ollama)
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=mistral

# LaraFlowAI Settings
LARAFLOWAI_DEFAULT_PROVIDER=openai
LARAFLOWAI_QUEUE_ENABLED=false
LARAFLOWAI_LOGGING_ENABLED=true
LARAFLOWAI_MEMORY_CACHE_TTL=3600

# MCP (Model Context Protocol) Settings
LARAFLOWAI_MCP_ENABLED=false
LARAFLOWAI_MCP_TIMEOUT=30
LARAFLOWAI_MCP_CACHE_TTL=3600
```

### Provider Configuration

Each provider can be configured in `config/laraflowai.php`:

```php
'providers' => [
    'openai' => [
        'driver' => \LaraFlowAI\Providers\OpenAIProvider::class,
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4'),
        'timeout' => 60,
    ],
    'grok' => [
        'driver' => \LaraFlowAI\Providers\GrokProvider::class,
        'api_key' => env('GROK_API_KEY'),
        'model' => env('GROK_MODEL', 'grok-4'),
        'timeout' => 120,
    ],
    'deepseek' => [
        'driver' => \LaraFlowAI\Providers\DeepSeekProvider::class,
        'api_key' => env('DEEPSEEK_API_KEY'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'timeout' => 60,
    ],
    // ... other providers
],
```

### Provider Modes

LaraFlowAI supports multiple provider modes to handle different types of AI tasks:

**Available Modes:**
- **Chat Mode** (Default): Conversational AI with message-based interactions
- **Completion Mode**: Text completion and generation tasks
- **Embedding Mode**: Vector embeddings for semantic search and similarity

**Usage Examples:**

```php
use LaraFlowAI\Facades\FlowAI;

// Chat Mode (Default)
$chatAgent = FlowAI::agent([
    'role' => 'Assistant',
    'goal' => 'Help users with questions',
    'provider' => 'openai',
    'config' => ['mode' => 'chat']
]);

// Completion Mode
$completionAgent = FlowAI::agent([
    'role' => 'Text Generator',
    'goal' => 'Generate text completions',
    'provider' => 'openai',
    'config' => ['mode' => 'completion']
]);

// Embedding Mode
$embeddingAgent = FlowAI::agent([
    'role' => 'Text Analyzer',
    'goal' => 'Generate text embeddings',
    'provider' => 'openai',
    'config' => ['mode' => 'embedding']
]);

// Dynamic Mode Switching
$agent = FlowAI::agent(['provider' => 'openai']);
$agent->setMode('chat');     // Switch to chat mode
$agent->setMode('completion'); // Switch to completion mode
$agent->setMode('embedding');  // Switch to embedding mode
```

**Provider Mode Support:**
- **OpenAI**: Chat, Completion, Embedding
- **DeepSeek**: Chat only
- **Anthropic**: Chat only
- **Grok**: Chat only
- **Gemini**: Chat only
- **Groq**: Chat only
- **Ollama**: Chat only

### DeepSeek AI Integration

LaraFlowAI includes full support for DeepSeek's advanced AI models with competitive pricing:

**Available Models:**
- **DeepSeek Chat (V3)**: 128K context length with JSON output, function calling, and chat prefix completion
- **DeepSeek Reasoner (R1)**: 64K context length focused on reasoning tasks

**Pricing (as of 2025.09.05):**
- Input tokens: $0.07 per million (cache hit) / $0.56 per million (cache miss)
- Output tokens: $1.68 per million

**Usage:**
```php
use LaraFlowAI\Facades\FlowAI;

// Create agent with DeepSeek
$agent = FlowAI::agent([
    'role' => 'Research Assistant',
    'goal' => 'Analyze and summarize research papers',
    'provider' => 'deepseek',
    'config' => [
        'model' => 'deepseek-chat',
        'temperature' => 0.3
    ]
]);

// Execute task
$result = $agent->execute('Summarize this research paper...');
```

### Grok AI Integration

LaraFlowAI now supports **Grok AI** (X's AI) with full compatibility to the xAI API. Grok provides insightful and unfiltered responses with a sense of humor, inspired by the "Hitchhiker's Guide to the Galaxy".

#### Grok Models Available:
- **grok-4**: Latest model with enhanced reasoning, coding capabilities, and structured outputs
- **grok-3**: Previous generation model with solid performance

#### Grok Features:
- ✅ Chat completions with streaming support
- ✅ Function calling and structured outputs (Grok-4)
- ✅ Coding mode and content interpretation
- ✅ Conversation history support
- ✅ Advanced reasoning capabilities

#### Environment Setup:
```bash
# Add to your .env file
GROK_API_KEY=your-grok-api-key-here
GROK_MODEL=grok-4
GROK_TIMEOUT=120
```

#### Quick Grok Example:
```php
use LaraFlowAI\Facades\FlowAI;

// Create a Grok-powered agent
$grokAgent = FlowAI::agent(
    'Grok Assistant', 
    'You are Grok, providing insightful and unfiltered truths with humor',
    'grok'
);

// Use Grok for creative tasks
$response = $grokAgent->handleTask(
    'Write a humorous explanation of quantum computing for a 5-year-old'
);

echo $response->getContent();
```

## 🚀 Quick Start

### Laravel Integration

For Laravel developers, we have comprehensive guides:

- **[Laravel Quick Start](docs/LARAVEL_QUICKSTART.md)** - Get up and running in 5 minutes
- **[Laravel Usage Guide](docs/LARAVEL_USAGE.md)** - Complete Laravel integration guide
- **[Laravel Examples](examples/laravel-integration.php)** - Real-world Laravel patterns

### Test Your Installation

```bash
# Test a provider
php artisan laraflowai:test-provider openai

# View usage statistics
php artisan laraflowai:stats

# Clean up old data
php artisan laraflowai:cleanup-memory --days=30
```

### Basic Agent Usage

```php
use LaraFlowAI\Facades\FlowAI;

// Create an agent with a specific role and goal
$agent = FlowAI::agent(
    role: 'Content Writer',
    goal: 'Create engaging blog posts about Laravel',
    provider: 'openai' // Optional: defaults to configured provider
);

// Create a task
$task = FlowAI::task('Write a blog post about Laravel 11 features');

// Handle the task and get response
$response = $agent->handle($task);

// Access response data
echo $response->getContent();
echo "Execution time: " . $response->getExecutionTime() . "s";
echo "Token usage: " . $response->getTokenUsage();
```

### Multiple Providers

```php
// Use different providers for different tasks
$openaiAgent = FlowAI::agent('Writer', 'High-quality content', 'openai');
$groqAgent = FlowAI::agent('Writer', 'Fast responses', 'groq');
$geminiAgent = FlowAI::agent('Writer', 'Google-powered insights', 'gemini');

// Compare responses
$task = FlowAI::task('Explain Laravel 11 features');
$openaiResponse = $openaiAgent->handle($task);
$groqResponse = $groqAgent->handle($task);
$geminiResponse = $geminiAgent->handle($task);
```

### Crew Usage

```php
use LaraFlowAI\Facades\FlowAI;

// Create specialized agents
$writer = FlowAI::agent('Content Writer', 'Write engaging content', 'openai');
$editor = FlowAI::agent('Editor', 'Review and improve content', 'groq');
$seo = FlowAI::agent('SEO Specialist', 'Optimize content for search engines', 'gemini');

// Create tasks with specific requirements
$tasks = [
    FlowAI::task('Write a comprehensive blog post about AI in web development')
        ->setContext(['target_audience' => 'developers', 'length' => '2000 words']),
    FlowAI::task('Review and edit the blog post for clarity and flow'),
    FlowAI::task('Optimize the blog post for SEO with relevant keywords'),
];

// Create crew with configuration
$crew = FlowAI::crew([
    'execution_mode' => 'sequential', // or 'parallel'
    'max_parallel_tasks' => 3,
    'timeout' => 300
])
    ->addAgent($writer)
    ->addAgent($editor)
    ->addAgent($seo)
    ->addTasks($tasks);

// Execute crew synchronously
$result = $crew->kickoff();

// Execute crew asynchronously (requires queue)
// $crew->kickoffAsync();

if ($result->isSuccess()) {
    echo "Crew executed successfully!\n";
    echo "Total execution time: " . $result->getExecutionTime() . "s\n";
    echo "Successful tasks: " . $result->getSuccessfulTaskCount() . "\n";
    
    foreach ($result->getResponses() as $index => $response) {
        echo "Task " . ($index + 1) . ":\n";
        echo $response->getContent() . "\n\n";
    }
} else {
    echo "Crew execution failed: " . $result->getErrorMessage() . "\n";
}
```

### Flow Usage

```php
use LaraFlowAI\Facades\FlowAI;
use LaraFlowAI\FlowStep;
use LaraFlowAI\FlowCondition;

// Create a complex workflow
$flow = FlowAI::flow('content_publishing_workflow');

// Add conditional steps
$flow->addStep(FlowStep::crew('content_creation', $crew))
    ->addStep(FlowStep::condition('quality_check', 
        FlowCondition::simple('quality_score', '>', 8)
    ))
    ->addStep(FlowStep::custom('improve_content', function($context) {
        // Improve content if quality is low
        $improver = FlowAI::agent('Content Improver', 'Enhance content quality');
        $task = FlowAI::task('Improve the content based on feedback');
        return $improver->handle($task);
    }))
    ->addStep(FlowStep::delay('review_delay', 10)) // 10 second delay
    ->addStep(FlowStep::custom('publish', function($context) {
        // Custom publishing logic
        return 'Published successfully to ' . $context['platform'];
    }));

// Run the flow with context
$result = $flow->run([
    'platform' => 'wordpress',
    'quality_score' => 7.5
]);

if ($result->isSuccess()) {
    echo "Flow completed: " . $result->getFinalResult() . "\n";
    echo "Steps executed: " . $result->getExecutedStepCount() . "\n";
} else {
    echo "Flow failed: " . $result->getErrorMessage() . "\n";
}
```

### Using Tools

```php
use LaraFlowAI\Tools\HttpTool;
use LaraFlowAI\Tools\DatabaseTool;
use LaraFlowAI\Tools\FilesystemTool;
use LaraFlowAI\Tools\MCPTool;

// Create a research agent with tools
$agent = FlowAI::agent('Research Assistant', 'Gather information from various sources')
    ->addTool(new HttpTool())
    ->addTool(new DatabaseTool())
    ->addTool(new FilesystemTool())
    ->addTool(new MCPTool());

// Create a task with tool inputs
$task = FlowAI::task('Research the latest Laravel features and create a summary')
    ->setToolInput('http', [
        'url' => 'https://laravel.com/news',
        'method' => 'GET',
        'headers' => ['User-Agent' => 'LaraFlowAI/1.0']
    ])
    ->setToolInput('database', [
        'query' => 'SELECT * FROM articles WHERE category = "laravel" ORDER BY created_at DESC LIMIT 10'
    ])
    ->setToolInput('filesystem', [
        'path' => '/path/to/research/notes.txt',
        'action' => 'read'
    ])
    ->setToolInput('mcp', [
        'server' => 'web_search_server',
        'action' => 'search_web',
        'parameters' => [
            'query' => 'Laravel 11 new features',
            'limit' => 10
        ]
    ]);

$response = $agent->handle($task);

// Access tool results
$toolResults = $response->getToolResults();
foreach ($toolResults as $tool => $result) {
    echo "Tool {$tool}: " . $result['status'] . "\n";
}
```

### MCP (Model Context Protocol) Integration

LaraFlowAI provides full support for external MCP servers, enabling integration with external tools and services:

#### Basic MCP Usage

```php
use LaraFlowAI\Tools\MCPTool;
use LaraFlowAI\MCP\MCPClient;

// Configure MCP servers in config/laraflowai.php
'mcp' => [
    'enabled' => true,
    'servers' => [
        'web_search' => [
            'name' => 'Web Search Server',
            'url' => 'http://localhost:3000/api/mcp',
            'auth_token' => env('MCP_WEB_SEARCH_TOKEN'),
            'enabled' => true,
            'available_actions' => ['search_web', 'get_weather', 'send_email']
        ],
        'analytics' => [
            'name' => 'Analytics Server',
            'url' => 'http://analytics:3000/api/mcp',
            'auth_token' => env('MCP_ANALYTICS_TOKEN'),
            'enabled' => true,
            'available_actions' => ['analyze_data', 'generate_report', 'create_chart']
        ]
    ]
];

// Use MCP tool with agents
$agent = FlowAI::agent('Research Assistant', 'Use external tools for research')
    ->addTool(new MCPTool());

$task = FlowAI::task('Search for information about AI trends')
    ->setToolInput('mcp', [
        'server' => 'web_search',
        'action' => 'search_web',
        'parameters' => [
            'query' => 'AI trends 2024',
            'limit' => 5
        ]
    ]);

$response = $agent->handle($task);
```

#### Advanced MCP Features

```php
// Direct MCP client usage
$mcpClient = app(MCPClient::class);

// Test server connections
if ($mcpClient->testConnection('web_search')) {
    echo "✅ Web search server is available\n";
}

// Get all available tools from all servers
$allTools = $mcpClient->getAllTools();
foreach ($allTools as $serverId => $tools) {
    echo "Server {$serverId} has " . count($tools) . " tools\n";
}

// Get server health status
$health = $mcpClient->getServerHealth('web_search');
echo "Server status: " . $health['status'] . "\n";
echo "Uptime: " . $health['uptime'] . "\n";
echo "Tools available: " . $health['tools_count'] . "\n";

// Execute multiple actions
$searchResult = $mcpClient->execute('web_search', 'search_web', [
    'query' => 'Laravel 11 features',
    'limit' => 3
]);

$analysisResult = $mcpClient->execute('analytics', 'analyze_data', [
    'data' => $searchResult,
    'analysis_type' => 'sentiment'
]);
```

#### MCP Server Management

```php
// Get server statistics
$stats = $mcpClient->getAllServerStats();
foreach ($stats as $serverId => $serverStats) {
    echo "Server: {$serverId}\n";
    echo "  Status: " . $serverStats['health_status'] . "\n";
    echo "  Tools: " . $serverStats['tools_count'] . "\n";
    echo "  Last check: " . $serverStats['last_health_check'] . "\n";
}

// Refresh tools cache
$mcpClient->clearCaches();
echo "✅ MCP caches cleared\n";

// Add new server dynamically
$mcpClient->addServer('new_server', [
    'name' => 'New MCP Server',
    'url' => 'http://new-server:3000/api/mcp',
    'auth_token' => 'new-token',
    'enabled' => true,
    'available_actions' => ['custom_action']
]);
```

#### MCP with Crews and Flows

```php
// Create specialized agents with different MCP servers
$researcher = FlowAI::agent('Web Researcher', 'Research from web sources')
    ->addTool(new MCPTool());

$analyst = FlowAI::agent('Data Analyst', 'Analyze research data')
    ->addTool(new MCPTool());

// Create tasks using different MCP servers
$researchTask = FlowAI::task('Research AI trends')
    ->setToolInput('mcp', [
        'server' => 'web_search',
        'action' => 'search_web',
        'parameters' => ['query' => 'AI trends 2024']
    ]);

$analysisTask = FlowAI::task('Analyze research data')
    ->setToolInput('mcp', [
        'server' => 'analytics',
        'action' => 'analyze_data',
        'parameters' => ['data_type' => 'research_results']
    ]);

// Execute with crew
$crew = FlowAI::crew(['execution_mode' => 'sequential'])
    ->addAgent($researcher)
    ->addAgent($analyst)
    ->addTask($researchTask)
    ->addTask($analysisTask);

$result = $crew->kickoff();
```

### Custom Tools

```php
use LaraFlowAI\Contracts\ToolContract;

class WeatherTool implements ToolContract
{
    public function getName(): string
    {
        return 'weather';
    }

    public function getDescription(): string
    {
        return 'Get current weather information for a location';
    }

    public function execute(array $inputs): array
    {
        $location = $inputs['location'] ?? 'New York';
        // Your weather API logic here
        return [
            'location' => $location,
            'temperature' => '72°F',
            'condition' => 'Sunny'
        ];
    }
}

// Register and use custom tool
$agent = FlowAI::agent('Weather Assistant', 'Provide weather information')
    ->addTool(new WeatherTool());

$task = FlowAI::task('What\'s the weather like in San Francisco?')
    ->setToolInput('weather', ['location' => 'San Francisco']);

$response = $agent->handle($task);
```

### Memory Usage

```php
use LaraFlowAI\Facades\FlowAI;

// Store information in memory
FlowAI::memory()->store('user_preferences', [
    'theme' => 'dark',
    'language' => 'en',
    'writing_style' => 'technical'
], 'long_term'); // or 'short_term'

// Recall specific information
$preferences = FlowAI::memory()->recall('user_preferences');

// Search memory with context
$results = FlowAI::memory()->search('Laravel features', [
    'limit' => 10,
    'type' => 'long_term'
]);

// Store conversation context
FlowAI::memory()->store('conversation_context', [
    'topic' => 'Laravel 11',
    'user_questions' => ['What are the new features?', 'How to migrate?'],
    'agent_responses' => ['Feature list...', 'Migration guide...']
]);

// Forget old information
FlowAI::memory()->forget('old_data', 'short_term');

// Clear all memory
FlowAI::memory()->clear('short_term');
```

### Advanced Memory Features

```php
// Memory with expiration
FlowAI::memory()->store('temporary_data', $data, 'short_term', 3600); // 1 hour

// Memory with tags for better organization
FlowAI::memory()->store('article_draft', $content, 'long_term', null, ['draft', 'laravel', 'tutorial']);

// Search by tags
$drafts = FlowAI::memory()->searchByTags(['draft', 'laravel']);

// Get memory statistics
$stats = FlowAI::memory()->getStats();
echo "Total memories: " . $stats['total'] . "\n";
echo "Short-term: " . $stats['short_term'] . "\n";
echo "Long-term: " . $stats['long_term'] . "\n";
```

### Custom LLM Provider

```php
use LaraFlowAI\Contracts\ProviderContract;
use LaraFlowAI\Providers\BaseProvider;

class CustomProvider extends BaseProvider
{
    protected function getDefaultModel(): string
    {
        return 'custom-model';
    }

    protected function getApiEndpoint(): string
    {
        return 'https://api.custom-ai.com/v1/chat';
    }

    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ];
    }

    protected function formatPayload(string $prompt, array $options = []): array
    {
        return [
            'model' => $this->model,
            'prompt' => $prompt,
            'max_tokens' => $options['max_tokens'] ?? 1000,
            'temperature' => $options['temperature'] ?? 0.7,
        ];
    }

    protected function extractResponse(array $response): string
    {
        return $response['choices'][0]['text'] ?? '';
    }

    protected function extractTokenUsage(array $response): array
    {
        return [
            'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
            'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
            'total_tokens' => $response['usage']['total_tokens'] ?? 0,
        ];
    }

    protected function calculateCost(int $promptTokens, int $completionTokens): float
    {
        return ($promptTokens + $completionTokens) * 0.0001; // $0.0001 per token
    }

    protected function getProviderName(): string
    {
        return 'custom';
    }
}

// Register the provider
FlowAI::extend('custom', function($config) {
    return new CustomProvider($config);
});

// Use the custom provider
$agent = FlowAI::agent('Custom Agent', 'Custom Goal', 'custom');
```

## 🚀 Advanced Features

### Custom Prompts

You can customize prompts by overriding the agent's prompt building method:

```php
$agent = FlowAI::agent('Expert', 'Provide expert advice')
    ->setConfig([
        'custom_prompt' => 'You are a {role} specializing in {specialty}. Your task: {task}. Please provide a detailed response.',
        'specialty' => 'Laravel development'
    ]);
```

### Queue Integration

Enable queue processing for long-running tasks:

```php
// In your .env
LARAFLOWAI_QUEUE_ENABLED=true
LARAFLOWAI_QUEUE_CONNECTION=redis

// Queue a crew execution
$crew->kickoffAsync();

// Queue a flow execution
$flow->runAsync();

// Check queue status
$jobId = $crew->kickoffAsync();
echo "Job queued with ID: " . $jobId;
```

### Monitoring and Logging

LaraFlowAI provides comprehensive logging and monitoring:

```php
// Check execution results
$result = $crew->kickoff();

echo "Execution time: " . $result->getExecutionTime() . " seconds\n";
echo "Successful tasks: " . $result->getSuccessfulTaskCount() . "\n";
echo "Failed tasks: " . $result->getFailedTaskCount() . "\n";

// View usage statistics
php artisan laraflowai:stats

// Test providers
php artisan laraflowai:test-provider openai
php artisan laraflowai:test-provider groq

# Test MCP servers
php artisan laraflowai:test-mcp --all
php artisan laraflowai:test-mcp web_search --health
php artisan laraflowai:test-mcp web_search --tools

# MCP server management
php artisan laraflowai:test-mcp --help

// Clean up old data
php artisan laraflowai:cleanup-memory --days=30
php artisan laraflowai:cleanup-tokens --days=7
```

### Event System

LaraFlowAI dispatches events for monitoring and integration:

```php
use LaraFlowAI\Events\CrewExecuted;
use LaraFlowAI\Events\CrewExecutionFailed;

// Listen to crew events
Event::listen(CrewExecuted::class, function ($event) {
    Log::info('Crew executed successfully', [
        'crew_id' => $event->crewId,
        'execution_time' => $event->executionTime,
        'successful_tasks' => $event->successfulTasks
    ]);
});

Event::listen(CrewExecutionFailed::class, function ($event) {
    Log::error('Crew execution failed', [
        'crew_id' => $event->crewId,
        'error' => $event->error
    ]);
});
```

### Middleware and Security

```php
// Rate limiting middleware
Route::middleware(['laraflowai.rate_limit'])->group(function () {
    Route::post('/api/ai/process', [AIController::class, 'process']);
});

// Authentication middleware
Route::middleware(['laraflowai.auth'])->group(function () {
    Route::get('/api/ai/stats', [AIController::class, 'stats']);
});
```

## ⚙️ Configuration Options

The package provides extensive configuration options in `config/laraflowai.php`:

- **Providers**: Configure OpenAI, Anthropic, Grok, Gemini, DeepSeek, Ollama, and custom providers
- **Memory**: Set up memory storage, caching, and expiration policies
- **MCP Servers**: Configure external Model Context Protocol servers
- **Logging**: Configure logging levels, channels, and structured logging
- **Queue**: Set up async processing with Laravel queues and Horizon
- **Cache**: Configure caching for better performance and cost optimization
- **Security**: Rate limiting, authentication, and input validation
- **Monitoring**: Token usage tracking, cost monitoring, and performance metrics

### MCP Server Configuration

Configure MCP servers in your `config/laraflowai.php`:

```php
'mcp' => [
    'enabled' => env('LARAFLOWAI_MCP_ENABLED', false),
    'default_timeout' => env('LARAFLOWAI_MCP_TIMEOUT', 30),
    'cache_tools_ttl' => env('LARAFLOWAI_MCP_CACHE_TTL', 3600),
    'retry_attempts' => env('LARAFLOWAI_MCP_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('LARAFLOWAI_MCP_RETRY_DELAY', 1000),
    
    'servers' => [
        'web_search' => [
            'name' => 'Web Search Server',
            'url' => env('MCP_WEB_SEARCH_URL', 'http://localhost:3000/api/mcp'),
            'auth_token' => env('MCP_WEB_SEARCH_TOKEN'),
            'timeout' => env('MCP_WEB_SEARCH_TIMEOUT', 30),
            'enabled' => env('MCP_WEB_SEARCH_ENABLED', false),
            'available_actions' => ['search_web', 'get_weather', 'send_email'],
            'description' => 'Web search and communication tools',
            'version' => '1.0.0',
            'health_check_interval' => 300
        ],
        'analytics' => [
            'name' => 'Analytics Server',
            'url' => env('MCP_ANALYTICS_URL', 'http://analytics:3000/api/mcp'),
            'auth_token' => env('MCP_ANALYTICS_TOKEN'),
            'timeout' => 60,
            'enabled' => true,
            'available_actions' => ['analyze_data', 'generate_report', 'create_chart'],
            'description' => 'Data analysis and visualization tools',
            'version' => '1.0.0'
        ]
    ],
    
    'default_headers' => [
        'User-Agent' => 'LaraFlowAI/1.0.0',
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ],
    
    'logging' => [
        'enabled' => env('LARAFLOWAI_MCP_LOGGING_ENABLED', true),
        'level' => env('LARAFLOWAI_MCP_LOG_LEVEL', 'info'),
        'log_requests' => env('LARAFLOWAI_MCP_LOG_REQUESTS', true),
        'log_responses' => env('LARAFLOWAI_MCP_LOG_RESPONSES', false)
    ]
]
```

## 📚 Documentation

- **[API Documentation](docs/API.md)** - Complete API reference
- **[Laravel Quick Start](docs/LARAVEL_QUICKSTART.md)** - 5-minute setup guide
- **[Laravel Usage Guide](docs/LARAVEL_USAGE.md)** - Comprehensive integration guide
- **[Examples](examples/)** - Real-world usage examples and patterns

## 🧪 Testing

```bash
# Run tests
composer test

# Run specific test suite
php artisan test --filter=AgentTest
php artisan test --filter=CrewTest
php artisan test --filter=MemoryTest
php artisan test --filter=MCPToolTest
php artisan test --filter=MCPClientTest

# Test with coverage
composer test-coverage
```

## 📊 Performance

LaraFlowAI is optimized for performance:

- **Caching**: Intelligent caching of responses and memory
- **Queue Integration**: Async processing for long-running tasks
- **Token Optimization**: Efficient token usage and cost tracking
- **Memory Management**: Smart memory cleanup and garbage collection
- **Provider Fallback**: Automatic fallback between providers

## 🔧 Troubleshooting

### Common Issues

1. **Provider not found**: Check your API keys and provider configuration
2. **Memory issues**: Run `php artisan laraflowai:cleanup-memory`
3. **Queue not working**: Ensure queue workers are running
4. **Token limits**: Check your provider's rate limits and quotas
5. **MCP server connection failed**: Check server URL, authentication, and network connectivity
6. **MCP tools not available**: Verify server is enabled and tools are properly configured

### Debug Mode

```bash
# Enable debug logging
LARAFLOWAI_DEBUG=true

# View detailed logs
tail -f storage/logs/laraflowai.log
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/laraflowai/laraflowai.git

# Install dependencies
composer install

# Run tests
composer test

# Run code quality checks
composer cs-fix
composer phpstan
```

## 📄 License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🆘 Support

- **GitHub Issues**: [Report bugs and request features](https://github.com/laraflowai/laraflowai/issues)
- **Documentation**: [Comprehensive guides and examples](https://github.com/laraflowai/laraflowai/tree/main/docs)
- **Community**: [Join our Discord community](https://discord.gg/laraflowai)

## 🚀 Quick Reference

### Essential Commands

```bash
# Installation
composer require laraflowai/laraflowai
php artisan vendor:publish --provider="LaraFlowAI\LaraFlowAIServiceProvider"
php artisan migrate

# Testing
php artisan laraflowai:test-provider openai
php artisan laraflowai:test-mcp --all
php artisan laraflowai:stats

# Maintenance
php artisan laraflowai:cleanup-memory --days=30
php artisan laraflowai:cleanup-tokens --days=7
```

### Environment Variables

```env
# AI Providers
OPENAI_API_KEY=your_key
ANTHROPIC_API_KEY=your_key
DEEPSEEK_API_KEY=your_key
GROQ_API_KEY=your_key
GEMINI_API_KEY=your_key

# MCP Servers
LARAFLOWAI_MCP_ENABLED=true
MCP_WEB_SEARCH_URL=http://localhost:3000/api/mcp
MCP_WEB_SEARCH_TOKEN=your_token
MCP_WEB_SEARCH_ENABLED=true
```

### Basic Usage

```php
use LaraFlowAI\Facades\FlowAI;
use LaraFlowAI\Tools\MCPTool;

// Create agent with MCP tool
$agent = FlowAI::agent('Assistant', 'Help users')
    ->addTool(new MCPTool());

// Create task with MCP input
$task = FlowAI::task('Search for information')
    ->setToolInput('mcp', [
        'server' => 'web_search',
        'action' => 'search_web',
        'parameters' => ['query' => 'Laravel 11']
    ]);

$response = $agent->handle($task);
```

## 🙏 Acknowledgments

- Inspired by [crewAI](https://github.com/joaomdmoura/crewAI)
- Built for the Laravel community
- Powered by multiple AI providers
- Enhanced with [Model Context Protocol](https://modelcontextprotocol.io) support

---

**Made with ❤️ for the Laravel community**
