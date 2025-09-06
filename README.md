# LaraFlowAI

[![Latest Version](https://img.shields.io/badge/version-alpha2-blue.svg)](https://packagist.org/packages/laraflowai/laraflowai)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-purple.svg)](https://php.net)

A powerful Laravel package for building multi-agent AI workflows. Create intelligent agents, crews, and flows with support for multiple AI providers and advanced workflow management.

## ✨ Features

- 🤖 **Multi-Agent System**: Create intelligent agents with specific roles and goals
- 👥 **Crew Management**: Organize agents into collaborative teams
- 🔄 **Flow Control**: Build sophisticated workflows with conditional logic
- 🧠 **Memory System**: Short-term and long-term memory with intelligent recall
- 🔌 **Multi-Provider Support**: OpenAI, Anthropic, Grok, Gemini, DeepSeek, and Ollama
- 🛠️ **Extensible Tools**: HTTP, Database, Filesystem, and custom tool implementations
- ⚡ **Queue Integration**: Asynchronous execution with Laravel queues
- 📊 **Observability**: Comprehensive logging and performance analytics

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

### Environment Setup

Add your API keys to your `.env` file:

```env
# AI Provider API Keys
OPENAI_API_KEY=your_openai_api_key
ANTHROPIC_API_KEY=your_anthropic_api_key
GROK_API_KEY=your_grok_api_key
GEMINI_API_KEY=your_gemini_api_key
DEEPSEEK_API_KEY=your_deepseek_api_key

# Local AI (Ollama)
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=llama3.2:3b

# LaraFlowAI Settings
LARAFLOWAI_DEFAULT_PROVIDER=openai
```

## 🚀 Quick Start

### Basic Agent Usage

```php
use LaraFlowAI\Facades\FlowAI;

// Create an agent
$agent = FlowAI::agent(
    role: 'Content Writer',
    goal: 'Create engaging blog posts about Laravel',
    provider: 'openai' // Optional: defaults to configured provider
);

// Create a task
$task = FlowAI::task('Write a blog post about Laravel 11 features');

// Handle the task
$response = $agent->handle($task);

// Access response
echo $response->getContent();
echo "Execution time: " . $response->getExecutionTime() . "s";
```

### Multiple Providers

```php
// Use different providers for different tasks
$openaiAgent = FlowAI::agent('Writer', 'High-quality content', 'openai');
$grokAgent = FlowAI::agent('Writer', 'Creative and humorous content', 'grok');
$geminiAgent = FlowAI::agent('Writer', 'Google-powered insights', 'gemini');

// Compare responses
$task = FlowAI::task('Explain Laravel 11 features');
$openaiResponse = $openaiAgent->handle($task);
$grokResponse = $grokAgent->handle($task);
$geminiResponse = $geminiAgent->handle($task);
```

### Crew Usage

```php
use LaraFlowAI\Facades\FlowAI;

// Create specialized agents
$writer = FlowAI::agent('Content Writer', 'Write engaging content', 'openai');
$editor = FlowAI::agent('Editor', 'Review and improve content', 'grok');

// Create tasks
$tasks = [
    FlowAI::task('Write a comprehensive blog post about AI in web development'),
    FlowAI::task('Review and improve the blog post from the previous task. The blog post is provided in the context.'),
];

// Create crew
$crew = FlowAI::crew()
    ->agents([$writer, $editor])
    ->tasks($tasks);

// Execute crew
$result = $crew->execute();

if ($result->isSuccess()) {
    echo "Crew executed successfully!\n";
    echo "Total execution time: " . $result->getExecutionTime() . "s\n";
    
    foreach ($result->getResults() as $index => $taskResult) {
        echo "Task " . ($index + 1) . ":\n";
        echo $taskResult['response']->getContent() . "\n\n";
    }
}
```

### Memory Usage

```php
use LaraFlowAI\Facades\FlowAI;

// Store information in memory
FlowAI::memory()->store('user_preferences', [
    'theme' => 'dark',
    'language' => 'en',
    'writing_style' => 'technical'
], 'long_term');

// Recall specific information
$preferences = FlowAI::memory()->recall('user_preferences');

// Search memory
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
```

### Using Tools

```php
use LaraFlowAI\Tools\HttpTool;
use LaraFlowAI\Tools\DatabaseTool;
use LaraFlowAI\Tools\FilesystemTool;

// Create a research agent with tools
$agent = FlowAI::agent('Research Assistant', 'Gather information from various sources')
    ->addTool(new HttpTool())
    ->addTool(new DatabaseTool())
    ->addTool(new FilesystemTool());

// Create a task with tool inputs
$task = FlowAI::task('Research the latest Laravel features and create a summary')
    ->setToolInput('http', [
        'url' => 'https://laravel.com/news',
        'method' => 'GET',
        'headers' => ['User-Agent' => 'LaraFlowAI/1.0']
    ])
    ->setToolInput('database', [
        'query' => 'SELECT * FROM articles WHERE category = "laravel" ORDER BY created_at DESC LIMIT 10'
    ]);

$response = $agent->handle($task);

// Access tool results
$toolResults = $response->getToolResults();
foreach ($toolResults as $tool => $result) {
    echo "Tool {$tool}: " . $result['status'] . "\n";
}
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

## 🔧 Configuration

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
    'gemini' => [
        'driver' => \LaraFlowAI\Providers\GeminiProvider::class,
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'timeout' => 60,
    ],
    // ... other providers
],
```

### Available Providers

- **OpenAI**: GPT-4, GPT-3.5-turbo with chat and completion modes
- **Anthropic**: Claude models with chat mode
- **Grok**: Grok-4, Grok-3 with chat mode and humor
- **Gemini**: Google's Gemini models with chat mode
- **DeepSeek**: DeepSeek Chat and Reasoner models
- **Ollama**: Local models like Llama, Mistral, etc.

## 🧪 Testing

```bash
# Test a provider
php artisan laraflowai:test-provider openai

# View usage statistics
php artisan laraflowai:stats

# Clean up old data
php artisan laraflowai:cleanup-memory --days=30
```

## 📊 Performance

LaraFlowAI is optimized for performance:

- **Caching**: Intelligent caching of responses and memory
- **Queue Integration**: Async processing for long-running tasks
- **Token Optimization**: Efficient token usage and cost tracking
- **Memory Management**: Smart memory cleanup and garbage collection

## 🔧 Troubleshooting

### Common Issues

1. **Provider not found**: Check your API keys and provider configuration
2. **Memory issues**: Run `php artisan laraflowai:cleanup-memory`
3. **Queue not working**: Ensure queue workers are running
4. **Token limits**: Check your provider's rate limits and quotas

### Debug Mode

```bash
# Enable debug logging
LARAFLOWAI_DEBUG=true

# View detailed logs
tail -f storage/logs/laraflowai.log
```

## 📚 Documentation

- **[API Documentation](docs/API.md)** - Complete API reference
- **[Laravel Quick Start](docs/LARAVEL_QUICKSTART.md)** - 5-minute setup guide
- **[Laravel Usage Guide](docs/LARAVEL_USAGE.md)** - Comprehensive integration guide
- **[Examples](examples/)** - Real-world usage examples and patterns

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

## 🙏 Acknowledgments

- Inspired by [crewAI](https://github.com/joaomdmoura/crewAI)
- Built for the Laravel community
- Powered by multiple AI providers

---

**Made with ❤️ for the Laravel community**