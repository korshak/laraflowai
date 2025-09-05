# LaraFlowAI Laravel Usage Guide

This guide shows you how to integrate and use LaraFlowAI in your Laravel applications.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Basic Usage](#basic-usage)
- [Advanced Features](#advanced-features)
- [Laravel Integration](#laravel-integration)
- [Queue Integration](#queue-integration)
- [Event Handling](#event-handling)
- [Testing](#testing)
- [Production Setup](#production-setup)
- [Troubleshooting](#troubleshooting)

## Installation

### Step 1: Install the Package

```bash
composer require laraflowai/laraflowai
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --provider="LaraFlowAI\LaraFlowAIServiceProvider"
```

This will publish:
- `config/laraflowai.php` - Main configuration file
- Database migrations for memory and token tracking

### Step 3: Run Migrations

```bash
php artisan migrate
```

### Step 4: Configure Environment Variables

Add to your `.env` file:

```env
# Required API Keys
OPENAI_API_KEY=your_openai_api_key_here
ANTHROPIC_API_KEY=your_anthropic_api_key_here
OLLAMA_HOST=http://localhost:11434

# Optional Configuration
LARAFLOWAI_DEFAULT_PROVIDER=openai
LARAFLOWAI_QUEUE_ENABLED=true
LARAFLOWAI_LOGGING_ENABLED=true
LARAFLOWAI_MEMORY_CACHE_TTL=3600
```

## Configuration

### Main Configuration File

The main configuration is in `config/laraflowai.php`:

```php
return [
    'default_provider' => env('LARAFLOWAI_DEFAULT_PROVIDER', 'openai'),
    
    'providers' => [
        'openai' => [
            'driver' => \LaraFlowAI\Providers\OpenAIProvider::class,
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4'),
        ],
        'anthropic' => [
            'driver' => \LaraFlowAI\Providers\AnthropicProvider::class,
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
        ],
        'ollama' => [
            'driver' => \LaraFlowAI\Providers\OllamaProvider::class,
            'host' => env('OLLAMA_HOST', 'http://localhost:11434'),
            'model' => env('OLLAMA_MODEL', 'mistral'),
        ],
    ],
    
    'memory' => [
        'driver' => 'database',
        'table' => 'laraflowai_memory',
        'cache_ttl' => env('LARAFLOWAI_MEMORY_CACHE_TTL', 3600),
    ],
    
    'queue' => [
        'enabled' => env('LARAFLOWAI_QUEUE_ENABLED', false),
        'connection' => env('LARAFLOWAI_QUEUE_CONNECTION', 'default'),
        'queue' => env('LARAFLOWAI_QUEUE_NAME', 'laraflowai'),
    ],
];
```

## Basic Usage

### Using the Facade

LaraFlowAI provides a clean facade for easy access:

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

### Service Container Integration

You can also use dependency injection:

```php
use LaraFlowAI\Contracts\MemoryContract;
use LaraFlowAI\LLMFactory;

class ContentController extends Controller
{
    public function __construct(
        private MemoryContract $memory,
        private LLMFactory $llmFactory
    ) {}
    
    public function generateContent(Request $request)
    {
        $provider = $this->llmFactory->driver('openai');
        $agent = new \LaraFlowAI\Agent('Writer', 'Create content', $provider, $this->memory);
        
        $task = new \LaraFlowAI\Task($request->input('description'));
        $response = $agent->handle($task);
        
        return response()->json(['content' => $response->getContent()]);
    }
}
```

## Advanced Features

### Crew Management

Create teams of agents for complex tasks:

```php
use LaraFlowAI\Facades\FlowAI;

class ContentTeam
{
    public function createContent(string $topic): array
    {
        // Create specialized agents
        $researcher = FlowAI::agent('Researcher', 'Gather information and facts');
        $writer = FlowAI::agent('Writer', 'Create engaging content');
        $editor = FlowAI::agent('Editor', 'Review and improve content');
        $seo = FlowAI::agent('SEO Specialist', 'Optimize for search engines');
        
        // Create tasks
        $tasks = [
            FlowAI::task("Research information about {$topic}"),
            FlowAI::task("Write a comprehensive article about {$topic}"),
            FlowAI::task("Edit and improve the article"),
            FlowAI::task("Optimize the article for SEO"),
        ];
        
        // Create and execute crew
        $crew = FlowAI::crew(['execution_mode' => 'sequential'])
            ->addAgent($researcher)
            ->addAgent($writer)
            ->addAgent($editor)
            ->addAgent($seo)
            ->addTasks($tasks);
        
        $result = $crew->kickoff();
        
        if ($result->isSuccess()) {
            return [
                'success' => true,
                'content' => $result->getResponses(),
                'execution_time' => $result->getExecutionTime(),
            ];
        }
        
        return [
            'success' => false,
            'error' => $result->getError(),
        ];
    }
}
```

### Flow Management

Create complex workflows with conditional execution:

```php
use LaraFlowAI\Facades\FlowAI;
use LaraFlowAI\FlowStep;
use LaraFlowAI\FlowCondition;

class ContentWorkflow
{
    public function processContent(string $topic): array
    {
        $flow = FlowAI::flow(['name' => 'Content Processing Workflow']);
        
        // Add workflow steps
        $flow->addStep(FlowStep::crew('content_creation', $this->createContentCrew()))
            ->addStep(FlowStep::condition('quality_check', FlowCondition::simple('quality_score', '>', 8)))
            ->addStep(FlowStep::custom('publish', function($context) {
                // Custom publishing logic
                return $this->publishContent($context);
            }))
            ->addStep(FlowStep::delay('notification_delay', 5))
            ->addStep(FlowStep::custom('notify', function($context) {
                // Send notifications
                return $this->sendNotifications($context);
            }));
        
        $result = $flow->run();
        
        return [
            'success' => $result->isSuccess(),
            'steps_completed' => $result->getSuccessfulStepCount(),
            'execution_time' => $result->getExecutionTime(),
            'results' => $result->getResults(),
        ];
    }
}
```

### Memory Integration

Use memory for context persistence:

```php
use LaraFlowAI\Facades\FlowAI;

class UserAssistant
{
    public function chatWithUser(string $userId, string $message): string
    {
        // Get user context from memory
        $userContext = FlowAI::memory()->recall("user_{$userId}_context") ?? [];
        
        // Create agent with user context
        $agent = FlowAI::agent(
            role: 'Personal Assistant',
            goal: 'Help the user with their tasks',
            provider: 'openai'
        )->setContext($userContext);
        
        // Create task with message
        $task = FlowAI::task($message);
        
        // Handle the task
        $response = $agent->handle($task);
        
        // Update user context in memory
        $userContext['last_message'] = $message;
        $userContext['last_response'] = $response->getContent();
        $userContext['timestamp'] = now();
        
        FlowAI::memory()->store("user_{$userId}_context", $userContext);
        
        return $response->getContent();
    }
}
```

## Laravel Integration

### Artisan Commands

LaraFlowAI includes several Artisan commands:

```bash
# View usage statistics
php artisan laraflowai:stats --days=30

# Clean up old memory data
php artisan laraflowai:cleanup-memory --days=90

# Clean up old token usage data
php artisan laraflowai:cleanup-tokens --days=90

# Test a provider
php artisan laraflowai:test-provider openai --model=gpt-4
```

### Custom Artisan Commands

Create your own commands that use LaraFlowAI:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use LaraFlowAI\Facades\FlowAI;

class GenerateContentCommand extends Command
{
    protected $signature = 'content:generate {topic : The topic to write about}';
    protected $description = 'Generate content using LaraFlowAI';

    public function handle()
    {
        $topic = $this->argument('topic');
        
        $this->info("Generating content about: {$topic}");
        
        $agent = FlowAI::agent('Content Writer', 'Create engaging articles');
        $task = FlowAI::task("Write a comprehensive article about {$topic}");
        
        $response = $agent->handle($task);
        
        $this->info('Content generated successfully!');
        $this->line($response->getContent());
        
        return Command::SUCCESS;
    }
}
```

### Model Integration

Integrate with Laravel models:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaraFlowAI\Facades\FlowAI;

class Article extends Model
{
    protected $fillable = ['title', 'content', 'status', 'ai_generated'];
    
    public function generateContent(string $topic): void
    {
        $agent = FlowAI::agent('Content Writer', 'Create engaging articles');
        $task = FlowAI::task("Write an article about {$topic}");
        
        $response = $agent->handle($task);
        
        $this->update([
            'content' => $response->getContent(),
            'ai_generated' => true,
            'status' => 'draft',
        ]);
    }
    
    public function improveContent(): void
    {
        $editor = FlowAI::agent('Editor', 'Improve content quality');
        $task = FlowAI::task("Improve this article: {$this->content}");
        
        $response = $editor->handle($task);
        
        $this->update([
            'content' => $response->getContent(),
        ]);
    }
}
```

### Controller Integration

Use in your controllers:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use LaraFlowAI\Facades\FlowAI;

class ContentController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'style' => 'nullable|string|in:formal,casual,technical',
        ]);
        
        $agent = FlowAI::agent(
            role: 'Content Writer',
            goal: 'Create engaging content',
            provider: $request->input('provider', 'openai')
        );
        
        $prompt = "Write a {$request->input('style', 'casual')} article about {$request->topic}";
        $task = FlowAI::task($prompt);
        
        $response = $agent->handle($task);
        
        return response()->json([
            'content' => $response->getContent(),
            'execution_time' => $response->getExecutionTime(),
        ]);
    }
    
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'user_id' => 'required|integer',
        ]);
        
        $userContext = FlowAI::memory()->recall("user_{$request->user_id}_context") ?? [];
        
        $agent = FlowAI::agent('Chat Assistant', 'Help users with their questions')
            ->setContext($userContext);
        
        $task = FlowAI::task($request->message);
        $response = $agent->handle($task);
        
        // Store conversation in memory
        $userContext['conversations'][] = [
            'message' => $request->message,
            'response' => $response->getContent(),
            'timestamp' => now(),
        ];
        
        FlowAI::memory()->store("user_{$request->user_id}_context", $userContext);
        
        return response()->json([
            'response' => $response->getContent(),
        ]);
    }
}
```

## Queue Integration

### Async Execution

For long-running tasks, use queue integration:

```php
use LaraFlowAI\Facades\FlowAI;

class ContentJob
{
    public function handle()
    {
        $crew = FlowAI::crew(['execution_mode' => 'sequential'])
            ->addAgent(FlowAI::agent('Writer', 'Create content'))
            ->addAgent(FlowAI::agent('Editor', 'Edit content'))
            ->addTasks([
                FlowAI::task('Write article about Laravel'),
                FlowAI::task('Edit the article'),
            ]);
        
        // Execute asynchronously
        $crew->kickoffAsync();
    }
}
```

### Queue Configuration

Configure queues in your `config/queue.php`:

```php
'connections' => [
    'laraflowai' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'laraflowai',
        'retry_after' => 90,
    ],
],
```

### Job Dispatching

```php
use App\Jobs\ContentGenerationJob;

// Dispatch job
ContentGenerationJob::dispatch($topic, $userId)
    ->onQueue('laraflowai')
    ->delay(now()->addMinutes(5));
```

## Event Handling

### Listen to Events

LaraFlowAI dispatches events for monitoring:

```php
<?php

namespace App\Listeners;

use LaraFlowAI\Events\CrewExecuted;
use Illuminate\Support\Facades\Log;

class CrewExecutionListener
{
    public function handle(CrewExecuted $event)
    {
        $result = $event->result;
        
        Log::info('Crew execution completed', [
            'execution_time' => $result->getExecutionTime(),
            'successful_tasks' => $result->getSuccessfulTaskCount(),
        ]);
        
        // Send notification, update database, etc.
    }
}
```

### Register Event Listeners

In `EventServiceProvider`:

```php
protected $listen = [
    \LaraFlowAI\Events\CrewExecuted::class => [
        \App\Listeners\CrewExecutionListener::class,
    ],
    \LaraFlowAI\Events\CrewExecutionFailed::class => [
        \App\Listeners\CrewFailureListener::class,
    ],
];
```

## Testing

### Unit Tests

Test your LaraFlowAI integration:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use LaraFlowAI\Facades\FlowAI;
use Mockery;

class ContentGenerationTest extends TestCase
{
    public function test_agent_generates_content()
    {
        $agent = FlowAI::agent('Test Writer', 'Generate test content');
        $task = FlowAI::task('Write a test article');
        
        $response = $agent->handle($task);
        
        $this->assertInstanceOf(\LaraFlowAI\Response::class, $response);
        $this->assertNotEmpty($response->getContent());
    }
    
    public function test_crew_execution()
    {
        $crew = FlowAI::crew()
            ->addAgent(FlowAI::agent('Writer', 'Write content'))
            ->addTask(FlowAI::task('Test task'));
        
        $result = $crew->kickoff();
        
        $this->assertTrue($result->isSuccess());
        $this->assertGreaterThan(0, $result->getTaskCount());
    }
}
```

### Feature Tests

Test API endpoints:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContentApiTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_generate_content_endpoint()
    {
        $response = $this->postJson('/api/content/generate', [
            'topic' => 'Laravel Testing',
            'style' => 'technical',
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'content',
                'execution_time',
            ]);
    }
}
```

## Production Setup

### Environment Configuration

For production, ensure proper configuration:

```env
# Production API Keys
OPENAI_API_KEY=sk-proj-your-production-key
ANTHROPIC_API_KEY=sk-ant-your-production-key

# Queue Configuration
LARAFLOWAI_QUEUE_ENABLED=true
LARAFLOWAI_QUEUE_CONNECTION=redis
LARAFLOWAI_QUEUE_NAME=laraflowai

# Logging
LARAFLOWAI_LOGGING_ENABLED=true
LARAFLOWAI_LOG_LEVEL=info

# Memory Configuration
LARAFLOWAI_MEMORY_CACHE_TTL=3600
LARAFLOWAI_MEMORY_CLEANUP_INTERVAL=86400
```

### Queue Workers

Set up queue workers for async processing:

```bash
# Start queue worker
php artisan queue:work --queue=laraflowai --tries=3 --timeout=300

# Or use Horizon
php artisan horizon
```

### Monitoring

Set up monitoring for token usage and costs:

```bash
# Check usage statistics
php artisan laraflowai:stats --days=7

# Clean up old data
php artisan laraflowai:cleanup-memory --days=30
php artisan laraflowai:cleanup-tokens --days=30
```

### Scheduled Tasks

Add cleanup tasks to your scheduler:

```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Clean up memory data older than 30 days
    $schedule->command('laraflowai:cleanup-memory --days=30')
        ->daily()
        ->at('02:00');
    
    // Clean up token usage data older than 90 days
    $schedule->command('laraflowai:cleanup-tokens --days=90')
        ->weekly()
        ->sundays()
        ->at('03:00');
}
```

## Troubleshooting

### Common Issues

1. **Provider not found**
   ```bash
   # Check configuration
   php artisan config:show laraflowai
   
   # Test provider
   php artisan laraflowai:test-provider openai
   ```

2. **Memory errors**
   ```bash
   # Check database connection
   php artisan migrate:status
   
   # Run migrations
   php artisan migrate
   ```

3. **Queue issues**
   ```bash
   # Check queue configuration
   php artisan queue:work --once
   
   # Check failed jobs
   php artisan queue:failed
   ```

4. **Token limits**
   ```bash
   # Check usage
   php artisan laraflowai:stats
   
   # Monitor costs
   php artisan laraflowai:stats --days=1
   ```

### Debug Mode

Enable debug logging:

```php
// In config/laraflowai.php
'logging' => [
    'enabled' => true,
    'level' => 'debug',
],
```

### Performance Optimization

1. **Use caching** for frequently accessed data
2. **Implement rate limiting** for API endpoints
3. **Use queues** for long-running operations
4. **Monitor token usage** to control costs
5. **Clean up old data** regularly

### Support

For issues and questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Run tests: `php artisan test`
- Check configuration: `php artisan config:show laraflowai`
- Review documentation: `docs/API.md`

## Best Practices

1. **Use appropriate providers** for your use case
2. **Implement proper error handling** for production
3. **Monitor token usage** to control costs
4. **Use memory effectively** for context persistence
5. **Test thoroughly** before deploying
6. **Use queues** for long-running operations
7. **Implement rate limiting** for API endpoints
8. **Monitor performance** using Artisan commands
9. **Clean up old data** regularly
10. **Use events** for monitoring and notifications

This guide provides everything you need to successfully integrate LaraFlowAI into your Laravel applications!
