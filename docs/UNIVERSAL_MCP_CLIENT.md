# Universal MCP Client

Універсальний клієнт для роботи з MCP (Model Context Protocol) серверами, який підтримує стандарт MCP протоколу та JSON-RPC 2.0.

## Особливості

- ✅ **Повна підтримка MCP протоколу** - JSON-RPC 2.0, стандартні MCP методи
- ✅ **Універсальність** - працює з будь-яким MCP сервером
- ✅ **Динамічне виявлення можливостей** - автоматичне визначення підтримуваних функцій
- ✅ **Кешування** - інтелектуальне кешування результатів
- ✅ **Retry логіка** - автоматичні повторні спроби при помилках
- ✅ **Типізація** - повна підтримка PHP 8.1+ типів
- ✅ **Обробка помилок** - специфічні exception класи
- ✅ **Логування** - детальне логування запитів та відповідей

## Встановлення

```bash
composer require laraflowai/laraflowai
```

## Конфігурація

```php
use LaraFlowAI\MCP\MCPClient;

$config = [
    'servers' => [
        'claude-mcp' => [
            'name' => 'Claude MCP Server',
            'url' => 'https://api.anthropic.com/mcp',
            'enabled' => true,
            'timeout' => 30,
            'auth_token' => 'your-api-key',
            'auth_type' => 'bearer', // bearer, api_key, basic
            'headers' => [
                'X-API-Version' => '2024-11-05'
            ]
        ],
        'openai-mcp' => [
            'name' => 'OpenAI MCP Server',
            'url' => 'https://api.openai.com/mcp',
            'enabled' => true,
            'timeout' => 30,
            'auth_token' => 'your-api-key',
            'auth_type' => 'bearer'
        ]
    ],
    'retry_attempts' => 3,
    'retry_delay' => 1000,
    'cache_tools_ttl' => 3600,
    'cache_resources_ttl' => 1800,
    'logging' => [
        'enabled' => true,
        'log_requests' => true,
        'log_responses' => false
    ]
];

$mcpClient = new MCPClient($config);
```

## Основне використання

### 1. Ініціалізація сервера

```php
// Ініціалізація з'єднання з MCP сервером
$response = $mcpClient->initialize('claude-mcp');

if ($response->isSuccess()) {
    echo "Server initialized successfully\n";
    $capabilities = $response->getResult()['capabilities'] ?? [];
} else {
    echo "Failed to initialize: " . $response->getErrorMessage() . "\n";
}
```

### 2. Робота з інструментами

```php
// Отримання списку інструментів
$tools = $mcpClient->getTools('claude-mcp');

foreach ($tools as $tool) {
    echo "Tool: {$tool->name}\n";
    echo "Description: {$tool->description}\n";
    echo "Input Schema: " . json_encode($tool->inputSchema) . "\n\n";
}

// Виклик інструменту
$response = $mcpClient->callTool('claude-mcp', 'search_web', [
    'query' => 'Laravel best practices',
    'limit' => 5
]);

if ($response->isSuccess()) {
    $results = $response->getResult();
    echo "Search results: " . json_encode($results, JSON_PRETTY_PRINT) . "\n";
}
```

### 3. Робота з ресурсами

```php
// Отримання списку ресурсів
$resources = $mcpClient->getResources('claude-mcp');

foreach ($resources as $resource) {
    echo "Resource: {$resource->name}\n";
    echo "URI: {$resource->uri}\n";
    echo "MIME Type: {$resource->mimeType}\n\n";
}

// Читання ресурсу
$response = $mcpClient->readResource('claude-mcp', 'file://documents/guide.md');

if ($response->isSuccess()) {
    $content = $response->getResult()['contents'] ?? [];
    echo "Resource content: " . json_encode($content, JSON_PRETTY_PRINT) . "\n";
}
```

### 4. Робота з промптами

```php
// Отримання списку промптів
$prompts = $mcpClient->getPrompts('claude-mcp');

// Отримання конкретного промпту
$response = $mcpClient->getPrompt('claude-mcp', 'code_review', [
    'language' => 'php',
    'code' => '<?php echo "Hello World"; ?>'
]);

if ($response->isSuccess()) {
    $prompt = $response->getResult();
    echo "Prompt: " . $prompt['messages'][0]['content'] . "\n";
}
```

### 5. Робота з зразками

```php
// Отримання списку зразків
$samples = $mcpClient->getSamples('claude-mcp');

// Отримання конкретного зразка
$response = $mcpClient->getSample('claude-mcp', 'api_example', [
    'endpoint' => '/users',
    'method' => 'GET'
]);

if ($response->isSuccess()) {
    $sample = $response->getResult();
    echo "Sample: " . json_encode($sample, JSON_PRETTY_PRINT) . "\n";
}
```

## Розширені можливості

### Перевірка з'єднання

```php
// Тестування з'єднання
$isConnected = $mcpClient->testConnection('claude-mcp');

if ($isConnected) {
    echo "Server is reachable\n";
} else {
    echo "Server is not reachable\n";
}
```

### Моніторинг здоров'я серверів

```php
// Отримання статусу здоров'я всіх серверів
$healthStatus = $mcpClient->getHealthStatus();

foreach ($healthStatus as $serverId => $status) {
    echo "Server: {$serverId}\n";
    echo "Status: {$status['status']}\n";
    echo "Tools: {$status['tools_count']}\n";
    echo "Resources: {$status['resources_count']}\n";
    echo "Last Check: {$status['last_check']}\n\n";
}
```

### Статистика серверів

```php
// Отримання детальної статистики
$stats = $mcpClient->getAllServerStats();

foreach ($stats as $serverId => $stat) {
    echo "Server: {$stat['name']}\n";
    echo "URL: {$stat['url']}\n";
    echo "Status: {$stat['health_status']}\n";
    echo "Capabilities: " . json_encode($stat['capabilities'], JSON_PRETTY_PRINT) . "\n\n";
}
```

### Управління кешем

```php
// Очищення кешу для конкретного сервера
$mcpClient->refreshCache('claude-mcp');

// Очищення всього кешу
$mcpClient->clearCaches();
```

### Перевірка можливостей сервера

```php
// Отримання можливостей сервера
$capabilities = $mcpClient->getServerCapabilities('claude-mcp');

// Перевірка підтримки конкретної можливості
if ($mcpClient->supportsCapability('claude-mcp', 'tools')) {
    echo "Server supports tools\n";
}

if ($mcpClient->supportsCapability('claude-mcp', 'resources')) {
    echo "Server supports resources\n";
}
```

## Обробка помилок

```php
use LaraFlowAI\MCP\Exceptions\MCPServerNotFoundException;
use LaraFlowAI\MCP\Exceptions\MCPConnectionException;
use LaraFlowAI\MCP\Exceptions\MCPExecutionException;

try {
    $response = $mcpClient->callTool('nonexistent-server', 'some_tool', []);
} catch (MCPServerNotFoundException $e) {
    echo "Server not found: " . $e->getMessage() . "\n";
} catch (MCPConnectionException $e) {
    echo "Connection error: " . $e->getMessage() . "\n";
} catch (MCPExecutionException $e) {
    echo "Execution error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->errorCode . "\n";
}
```

## Підтримувані MCP методи

### Основні методи
- `initialize` - ініціалізація з'єднання
- `ping` - перевірка з'єднання
- `tools/list` - список інструментів
- `tools/call` - виклик інструменту
- `resources/list` - список ресурсів
- `resources/read` - читання ресурсу
- `prompts/list` - список промптів
- `prompts/get` - отримання промпту
- `samples/list` - список зразків
- `samples/get` - отримання зразка

### Сповіщення
- `notifications/tools/list_changed`
- `notifications/resources/list_changed`
- `notifications/prompts/list_changed`
- `notifications/samples/list_changed`

## Конфігурація Laravel

Додайте в `config/laraflowai.php`:

```php
'mcp' => [
    'servers' => [
        'claude-mcp' => [
            'name' => 'Claude MCP Server',
            'url' => env('CLAUDE_MCP_URL', 'https://api.anthropic.com/mcp'),
            'enabled' => env('CLAUDE_MCP_ENABLED', true),
            'timeout' => env('CLAUDE_MCP_TIMEOUT', 30),
            'auth_token' => env('CLAUDE_API_KEY'),
            'auth_type' => 'bearer',
        ],
    ],
    'retry_attempts' => env('MCP_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('MCP_RETRY_DELAY', 1000),
    'cache_tools_ttl' => env('MCP_CACHE_TOOLS_TTL', 3600),
    'logging' => [
        'enabled' => env('MCP_LOGGING_ENABLED', true),
        'log_requests' => env('MCP_LOG_REQUESTS', true),
        'log_responses' => env('MCP_LOG_RESPONSES', false),
    ],
],
```

## Приклади використання

Дивіться файл `examples/universal-mcp-usage.php` для повного прикладу використання.

## Відмінності від попередньої версії

### ✅ Що покращено:
- Повна підтримка MCP протоколу
- JSON-RPC 2.0 стандарт
- Універсальність для будь-яких MCP серверів
- Динамічне виявлення можливостей
- Типізація та DTO класи
- Специфічні exception класи
- Покращена обробка помилок

### ❌ Що видалено:
- Жорстко закодована логіка для JSONPlaceholder
- Фіксовані структури запитів
- Нестандартні методи
- Застарілі API

Тепер MCPClient є справжнім універсальним клієнтом для роботи з будь-якими MCP серверами!
