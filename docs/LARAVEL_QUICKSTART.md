# LaraFlowAI Laravel Quick Start

Get up and running with LaraFlowAI in your Laravel application in 5 minutes!

## 🚀 Quick Installation

```bash
# 1. Install the package
composer require laraflowai/laraflowai

# 2. Publish configuration
php artisan vendor:publish --provider="LaraFlowAI\LaraFlowAIServiceProvider"

# 3. Run migrations
php artisan migrate

# 4. Add API keys to .env
echo "OPENAI_API_KEY=your_api_key_here" >> .env
echo "GROQ_API_KEY=your_groq_key_here" >> .env
echo "GEMINI_API_KEY=your_gemini_key_here" >> .env
```

## ⚡ Basic Usage

### Simple Agent

```php
use LaraFlowAI\Facades\FlowAI;

// Create an agent
$agent = FlowAI::agent('Writer', 'Create content');

// Create a task
$task = FlowAI::task('Write about Laravel 11');

// Get response
$response = $agent->handle($task);
echo $response->getContent();
```

### In a Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use LaraFlowAI\Facades\FlowAI;

class ContentController extends Controller
{
    public function generate(Request $request)
    {
        $agent = FlowAI::agent('Content Writer', 'Create engaging articles');
        $task = FlowAI::task($request->input('prompt'));
        
        $response = $agent->handle($task);
        
        return response()->json([
            'content' => $response->getContent()
        ]);
    }
}
```

### In a Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaraFlowAI\Facades\FlowAI;

class Article extends Model
{
    public function generateContent(string $topic): void
    {
        $agent = FlowAI::agent('Writer', 'Create articles');
        $task = FlowAI::task("Write about {$topic}");
        
        $response = $agent->handle($task);
        
        $this->update(['content' => $response->getContent()]);
    }
}
```

## 👥 Team of Agents (Crew)

```php
use LaraFlowAI\Facades\FlowAI;

// Create multiple agents
$writer = FlowAI::agent('Writer', 'Write content');
$editor = FlowAI::agent('Editor', 'Edit content');
$seo = FlowAI::agent('SEO', 'Optimize for search');

// Create tasks
$tasks = [
    FlowAI::task('Write about Laravel'),
    FlowAI::task('Edit the content'),
    FlowAI::task('Add SEO keywords'),
];

// Execute as a team
$crew = FlowAI::crew()
    ->addAgent($writer)
    ->addAgent($editor)
    ->addAgent($seo)
    ->addTasks($tasks);

$result = $crew->kickoff();

if ($result->isSuccess()) {
    foreach ($result->getResponses() as $response) {
        echo $response->getContent() . "\n";
    }
}
```

## 🔄 Workflow (Flow)

```php
use LaraFlowAI\Facades\FlowAI;
use LaraFlowAI\FlowStep;

$flow = FlowAI::flow()
    ->addStep(FlowStep::crew('content_creation', $crew))
    ->addStep(FlowStep::delay('wait', 5))
    ->addStep(FlowStep::custom('publish', function($context) {
        return 'Published successfully!';
    }));

$result = $flow->run();
```

## 🧠 Memory

```php
use LaraFlowAI\Facades\FlowAI;

// Store information
FlowAI::memory()->store('user_prefs', [
    'style' => 'casual',
    'length' => 'short'
]);

// Recall information
$prefs = FlowAI::memory()->recall('user_prefs');

// Search memory
$results = FlowAI::memory()->search('Laravel');
```

## 🛠️ Tools

```php
use LaraFlowAI\Facades\FlowAI;
use LaraFlowAI\Tools\HttpTool;
use LaraFlowAI\Tools\DatabaseTool;

// Add tools to agent
$agent = FlowAI::agent('Researcher', 'Gather information')
    ->addTool(new HttpTool())
    ->addTool(new DatabaseTool());

// Use tools in task
$task = FlowAI::task('Research Laravel features')
    ->setToolInput('http', [
        'url' => 'https://laravel.com/news',
        'method' => 'GET'
    ]);

$response = $agent->handle($task);
```

## ⚡ Async Processing

```php
use LaraFlowAI\Facades\FlowAI;

// Enable queues in .env
// LARAFLOWAI_QUEUE_ENABLED=true

$crew = FlowAI::crew()
    ->addAgent(FlowAI::agent('Writer', 'Write content'))
    ->addTask(FlowAI::task('Long article'));

// Execute in background
$crew->kickoffAsync();
```

## 📊 Monitoring

```bash
# View usage statistics
php artisan laraflowai:stats

# Test a provider
php artisan laraflowai:test-provider openai

# Clean up old data
php artisan laraflowai:cleanup-memory --days=30
```

## 🔧 Configuration

Edit `config/laraflowai.php`:

```php
return [
    'default_provider' => 'openai',
    'providers' => [
        'openai' => [
            'driver' => \LaraFlowAI\Providers\OpenAIProvider::class,
            'api_key' => env('OPENAI_API_KEY'),
            'model' => 'gpt-4',
        ],
        'groq' => [
            'driver' => \LaraFlowAI\Providers\GroqProvider::class,
            'api_key' => env('GROQ_API_KEY'),
            'model' => 'llama3-8b-8192',
        ],
        'gemini' => [
            'driver' => \LaraFlowAI\Providers\GeminiProvider::class,
            'api_key' => env('GEMINI_API_KEY'),
            'model' => 'gemini-pro',
        ],
        // Add more providers...
    ],
    'queue' => [
        'enabled' => env('LARAFLOWAI_QUEUE_ENABLED', false),
    ],
];
```

## 🚀 Multiple Providers

Use different AI providers for different tasks:

```php
use LaraFlowAI\Facades\FlowAI;

// Fast responses with Groq
$groqAgent = FlowAI::agent('Fast Writer', 'Quick content', 'groq');

// High quality with OpenAI
$openaiAgent = FlowAI::agent('Quality Writer', 'Premium content', 'openai');

// Google's Gemini
$geminiAgent = FlowAI::agent('Google Writer', 'Google-powered content', 'gemini');

// Compare responses
$task = FlowAI::task('Write about Laravel 11');
$groqResponse = $groqAgent->handle($task);
$openaiResponse = $openaiAgent->handle($task);
$geminiResponse = $geminiAgent->handle($task);
```

## 🧪 Testing

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use LaraFlowAI\Facades\FlowAI;

class ContentTest extends TestCase
{
    public function test_agent_generates_content()
    {
        $agent = FlowAI::agent('Test Writer', 'Generate test content');
        $task = FlowAI::task('Write a test article');
        
        $response = $agent->handle($task);
        
        $this->assertNotEmpty($response->getContent());
    }
}
```

## 🚨 Error Handling

```php
use LaraFlowAI\Facades\FlowAI;
use LaraFlowAI\Exceptions\LaraFlowAIException;

try {
    $agent = FlowAI::agent('Writer', 'Create content', 'invalid-provider');
    $response = $agent->handle(FlowAI::task('Test'));
} catch (LaraFlowAIException $e) {
    // Handle LaraFlowAI specific errors
    logger()->error('LaraFlowAI Error: ' . $e->getMessage());
}
```

## 📝 Artisan Commands

```bash
# Available commands
php artisan list laraflowai

# Usage statistics
php artisan laraflowai:stats --days=7

# Test providers
php artisan laraflowai:test-provider openai --model=gpt-4

# Cleanup data
php artisan laraflowai:cleanup-memory --days=90
php artisan laraflowai:cleanup-tokens --days=90
```

## 🎯 Common Use Cases

### 1. Content Generation API

```php
// routes/api.php
Route::post('/generate-content', function (Request $request) {
    $agent = FlowAI::agent('Content Writer', 'Create engaging content');
    $task = FlowAI::task($request->input('prompt'));
    
    $response = $agent->handle($task);
    
    return response()->json([
        'content' => $response->getContent(),
        'execution_time' => $response->getExecutionTime(),
    ]);
});
```

### 2. Chat Bot

```php
// In a controller
public function chat(Request $request)
{
    $userContext = FlowAI::memory()->recall("user_{$request->user_id}");
    
    $agent = FlowAI::agent('Chat Assistant', 'Help users')
        ->setContext($userContext);
    
    $response = $agent->handle(FlowAI::task($request->message));
    
    // Store conversation
    FlowAI::memory()->store("user_{$request->user_id}", [
        'last_message' => $request->message,
        'last_response' => $response->getContent(),
    ]);
    
    return response()->json(['response' => $response->getContent()]);
}
```

### 3. Data Processing

```php
use LaraFlowAI\Tools\DatabaseTool;

$agent = FlowAI::agent('Data Analyst', 'Analyze data')
    ->addTool(new DatabaseTool());

$task = FlowAI::task('Analyze user engagement data')
    ->setToolInput('database', [
        'query' => 'SELECT * FROM user_activities WHERE created_at > ?',
        'bindings' => [now()->subDays(30)],
        'type' => 'select'
    ]);

$response = $agent->handle($task);
```

## 🔗 Next Steps

1. **Read the full documentation**: `docs/LARAVEL_USAGE.md`
2. **Explore examples**: `examples/backend-only-usage.php`
3. **Check API reference**: `docs/API.md`
4. **Run tests**: `php artisan test`

## 🆘 Need Help?

- Check Laravel logs: `storage/logs/laravel.log`
- Run diagnostics: `php artisan laraflowai:test-provider openai`
- View configuration: `php artisan config:show laraflowai`
- Read full docs: `docs/LARAVEL_USAGE.md`

---

**You're ready to build AI-powered Laravel applications! 🎉**
