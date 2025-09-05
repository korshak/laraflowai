# LaraFlowAI API Documentation

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Core Classes](#core-classes)
- [Usage Examples](#usage-examples)
- [API Reference](#api-reference)
- [Events](#events)
- [Commands](#commands)
- [Middleware](#middleware)
- [Testing](#testing)

## Installation

```bash
composer require laraflowai/laraflowai
```

Publish configuration and migrations:

```bash
php artisan vendor:publish --provider="LaraFlowAI\LaraFlowAIServiceProvider"
```

## Configuration

### Environment Variables

```env
# API Keys
OPENAI_API_KEY=your_openai_api_key
ANTHROPIC_API_KEY=your_anthropic_api_key
GROQ_API_KEY=your_groq_api_key
GEMINI_API_KEY=your_gemini_api_key
OLLAMA_HOST=http://localhost:11434

# LaraFlowAI Configuration
LARAFLOWAI_DEFAULT_PROVIDER=openai
LARAFLOWAI_QUEUE_ENABLED=false
LARAFLOWAI_LOGGING_ENABLED=true
```

### Configuration File

The main configuration is in `config/laraflowai.php`:

```php
return [
    'default_provider' => 'openai',
    'providers' => [
        'openai' => [
            'driver' => \LaraFlowAI\Providers\OpenAIProvider::class,
            'api_key' => env('OPENAI_API_KEY'),
            'model' => 'gpt-4',
        ],
        // ... other providers
    ],
    'memory' => [
        'driver' => 'database',
        'table' => 'laraflowai_memory',
        'cache_ttl' => 3600,
    ],
    // ... other settings
];
```

## Supported Providers

LaraFlowAI supports multiple AI providers:

### OpenAI
- **Models**: GPT-4, GPT-3.5-turbo, GPT-4-turbo
- **Features**: High quality responses, streaming support
- **Best for**: Complex reasoning, creative writing, code generation

### Groq
- **Models**: Llama3-8b-8192, Llama3-70b-8192, Mixtral-8x7b-32768
- **Features**: Very fast responses, cost-effective
- **Best for**: Quick responses, high-volume applications

### Google Gemini
- **Models**: gemini-pro, gemini-1.5-pro, gemini-1.5-flash
- **Features**: Google-powered AI, multimodal capabilities
- **Best for**: Research, analysis, Google ecosystem integration

### Anthropic Claude
- **Models**: claude-3-opus, claude-3-sonnet, claude-3-haiku
- **Features**: Long context, safety-focused
- **Best for**: Long documents, safety-critical applications

### Ollama (Local)
- **Models**: mistral, llama2, codellama, etc.
- **Features**: Local execution, privacy-focused
- **Best for**: Private deployments, offline usage

## Core Classes

### Agent

The `Agent` class represents an AI agent with a specific role and goal.

```php
use LaraFlowAI\Facades\FlowAI;

$agent = FlowAI::agent(
    role: 'Content Writer',
    goal: 'Create engaging blog posts',
    provider: 'openai'
);
```

#### Methods

- `handle(Task $task): Response` - Handle a task
- `addTool(ToolContract $tool): self` - Add a tool
- `removeTool(string $toolName): self` - Remove a tool
- `setContext(array $context): self` - Set context
- `addContext(string $key, mixed $value): self` - Add to context

### Task

The `Task` class represents a unit of work for an agent.

```php
$task = FlowAI::task('Write a blog post about Laravel 11')
    ->setAgent('Content Writer')
    ->setToolInput('http', ['url' => 'https://laravel.com/news']);
```

#### Methods

- `setAgent(string $agent): self` - Set the agent
- `setToolInput(string $toolName, array $input): self` - Set tool input
- `setContext(array $context): self` - Set context
- `addContext(string $key, mixed $value): self` - Add to context

### Crew

The `Crew` class manages multiple agents working together.

```php
$crew = FlowAI::crew(['execution_mode' => 'sequential'])
    ->addAgent($writer)
    ->addAgent($editor)
    ->addTasks($tasks);

$result = $crew->kickoff();
```

#### Methods

- `addAgent(Agent $agent): self` - Add an agent
- `addTask(Task $task): self` - Add a task
- `kickoff(): CrewResult` - Execute the crew
- `kickoffAsync(): void` - Execute asynchronously

### Flow

The `Flow` class manages complex workflows with conditional execution.

```php
$flow = FlowAI::flow()
    ->addStep(FlowStep::crew('content_creation', $crew))
    ->addStep(FlowStep::condition('quality_check', $condition))
    ->addStep(FlowStep::custom('publish', $handler));

$result = $flow->run();
```

#### Methods

- `addStep(FlowStep $step): self` - Add a step
- `addCondition(FlowCondition $condition): self` - Add a condition
- `onEvent(string $event, callable $handler): self` - Add event handler
- `run(): FlowResult` - Execute the flow
- `runAsync(): void` - Execute asynchronously

## Usage Examples

### Basic Agent Usage

```php
use LaraFlowAI\Facades\FlowAI;

// Create an agent
$agent = FlowAI::agent(
    role: 'Content Writer',
    goal: 'Create engaging blog posts about Laravel',
    provider: 'openai'
);

// Create a task
$task = FlowAI::task('Write a blog post about Laravel 11 features');

// Handle the task
$response = $agent->handle($task);
echo $response->getContent();
```

### Crew Usage

```php
// Create agents
$writer = FlowAI::agent('Content Writer', 'Write engaging content');
$editor = FlowAI::agent('Editor', 'Review and improve content');
$seo = FlowAI::agent('SEO Specialist', 'Optimize content for search engines');

// Create tasks
$tasks = [
    FlowAI::task('Write a blog post about AI in web development'),
    FlowAI::task('Review and edit the blog post'),
    FlowAI::task('Optimize the blog post for SEO'),
];

// Create and execute crew
$crew = FlowAI::crew(['execution_mode' => 'sequential'])
    ->addAgent($writer)
    ->addAgent($editor)
    ->addAgent($seo)
    ->addTasks($tasks);

$result = $crew->kickoff();

if ($result->isSuccess()) {
    foreach ($result->getResponses() as $response) {
        echo $response->getContent() . "\n\n";
    }
}
```

### Flow Usage

```php
use LaraFlowAI\FlowStep;
use LaraFlowAI\FlowCondition;

$flow = FlowAI::flow();

// Add steps
$flow->addStep(FlowStep::crew('content_creation', $crew))
    ->addStep(FlowStep::condition('quality_check', FlowCondition::simple('quality_score', '>', 8)))
    ->addStep(FlowStep::delay('publish_delay', 5))
    ->addStep(FlowStep::custom('publish', function($context) {
        return 'Published successfully';
    }));

// Run the flow
$result = $flow->run();
```

### Memory Usage

```php
// Store information
FlowAI::memory()->store('user_preferences', [
    'theme' => 'dark',
    'language' => 'en'
]);

// Recall information
$preferences = FlowAI::memory()->recall('user_preferences');

// Search memory
$results = FlowAI::memory()->search('Laravel features');
```

### Tool Usage

```php
use LaraFlowAI\Tools\HttpTool;
use LaraFlowAI\Tools\DatabaseTool;

// Add tools to an agent
$agent = FlowAI::agent('Research Assistant', 'Gather information')
    ->addTool(new HttpTool())
    ->addTool(new DatabaseTool());

// Create a task with tool inputs
$task = FlowAI::task('Research the latest Laravel features')
    ->setToolInput('http', [
        'url' => 'https://laravel.com/news',
        'method' => 'GET'
    ]);

$response = $agent->handle($task);
```

## API Reference

### FlowAI Facade

The `FlowAI` facade provides access to all LaraFlowAI functionality:

```php
use LaraFlowAI\Facades\FlowAI;

// Get an LLM provider
$provider = FlowAI::llm('openai');

// Create an agent
$agent = FlowAI::agent('Role', 'Goal', 'provider');

// Create a task
$task = FlowAI::task('Description');

// Create a crew
$crew = FlowAI::crew(['config' => 'value']);

// Create a flow
$flow = FlowAI::flow(['config' => 'value']);

// Get memory manager
$memory = FlowAI::memory();

// Register custom provider
FlowAI::extend('custom', fn($config) => new CustomProvider($config));
```

### Response Classes

#### Response

```php
$response = $agent->handle($task);

// Get content
$content = $response->getContent();

// Get agent
$agent = $response->getAgent();

// Get tool results
$toolResults = $response->getToolResults();

// Get execution time
$time = $response->getExecutionTime();
```

#### CrewResult

```php
$result = $crew->kickoff();

// Check success
$success = $result->isSuccess();

// Get all responses
$responses = $result->getResponses();

// Get execution time
$time = $result->getExecutionTime();

// Get summary
$summary = $result->getSummary();
```

#### FlowResult

```php
$result = $flow->run();

// Check success
$success = $result->isSuccess();

// Get results by type
$crewResults = $result->getCrewResults();
$conditionResults = $result->getConditionResults();

// Get summary
$summary = $result->getSummary();
```

## Events

LaraFlowAI dispatches events for monitoring and integration:

### Crew Events

- `CrewExecuted` - Fired when a crew execution completes
- `CrewExecutionFailed` - Fired when a crew execution fails

### Flow Events

- `FlowExecuted` - Fired when a flow execution completes
- `FlowExecutionFailed` - Fired when a flow execution fails

### Event Listeners

```php
use LaraFlowAI\Events\CrewExecuted;

Event::listen(CrewExecuted::class, function ($event) {
    $result = $event->result;
    // Handle crew completion
});
```

## Commands

LaraFlowAI includes several Artisan commands:

### Cleanup Commands

```bash
# Clean up expired memory data
php artisan laraflowai:cleanup-memory --days=90

# Clean up old token usage data
php artisan laraflowai:cleanup-tokens --days=90
```

### Statistics Command

```bash
# Show usage statistics
php artisan laraflowai:stats --days=30
```

### Test Provider Command

```bash
# Test a specific provider
php artisan laraflowai:test-provider openai --model=gpt-4
```

## Middleware

LaraFlowAI includes middleware for security and rate limiting:

### Rate Limiting

```php
Route::middleware([\LaraFlowAI\Http\Middleware\RateLimitMiddleware::class])
    ->group(function () {
        // Your routes
    });
```

### Authentication

```php
Route::middleware([\LaraFlowAI\Http\Middleware\AuthMiddleware::class])
    ->group(function () {
        // Your routes
    });
```

## Testing

LaraFlowAI includes a comprehensive test suite:

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter=AgentTest
```

### Test Helpers

```php
use LaraFlowAI\Tests\TestCase;

class MyTest extends TestCase
{
    public function test_agent_creation()
    {
        $agent = FlowAI::agent('Test', 'Goal');
        $this->assertInstanceOf(Agent::class, $agent);
    }
}
```

## Dashboard

LaraFlowAI includes a web dashboard for monitoring:

- **URL**: `/laraflowai`
- **Features**: Usage statistics, memory management, cost tracking
- **Authentication**: Required (uses Laravel auth)

### Dashboard API

- `GET /laraflowai/usage` - Get usage statistics
- `GET /laraflowai/memory` - Get memory statistics
- `POST /laraflowai/cleanup` - Clean up old data

## Error Handling

LaraFlowAI provides comprehensive error handling:

```php
use LaraFlowAI\Exceptions\LaraFlowAIException;

try {
    $agent = FlowAI::agent('Role', 'Goal', 'invalid-provider');
} catch (LaraFlowAIException $e) {
    // Handle LaraFlowAI specific errors
    echo $e->getMessage();
}
```

### Exception Types

- `LaraFlowAIException` - Base exception
- `ProviderNotFoundException` - Provider not found
- `ToolNotFoundException` - Tool not found
- `MemoryOperationFailedException` - Memory operation failed
- `ValidationFailedException` - Validation failed

## Best Practices

1. **Use appropriate providers** for your use case
2. **Implement proper error handling** for production
3. **Monitor token usage** to control costs
4. **Use memory effectively** for context persistence
5. **Test thoroughly** before deploying
6. **Use queues** for long-running operations
7. **Implement rate limiting** for API endpoints
8. **Monitor performance** using the dashboard

## Troubleshooting

### Common Issues

1. **Provider not found**: Check your configuration and API keys
2. **Memory errors**: Ensure database migrations are run
3. **Queue issues**: Check queue configuration and workers
4. **Rate limiting**: Adjust rate limit settings
5. **Token limits**: Monitor usage and adjust limits

### Debug Mode

Enable debug logging in your configuration:

```php
'logging' => [
    'enabled' => true,
    'level' => 'debug',
],
```

### Support

For support and questions:
- Check the documentation
. Run tests to verify installation
- Check Laravel logs for errors
- Review configuration settings
