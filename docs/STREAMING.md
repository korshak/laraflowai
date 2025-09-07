# LaraFlowAI Streaming Responses

LaraFlowAI підтримує streaming responses для real-time виводу, що значно покращує UX у чат-інтерфейсах та інтерактивних додатках.

## Огляд

Streaming responses дозволяють отримувати відповіді від AI агентів по частинах (token-by-token) замість очікування повної відповіді. Це особливо корисно для:

- **Чат-інтерфейсів** - користувачі бачать відповідь в реальному часі
- **Livewire/Vue компонентів** - динамічне оновлення UI
- **Довгих відповідей** - покращує сприйняття швидкості
- **Інтерактивних додатків** - більш engaging користувацький досвід

## Основне використання

### Agent Streaming

```php
use LaraFlowAI\Facades\FlowAI;

$agent = FlowAI::agent(
    role: 'Content Writer',
    goal: 'Create engaging content',
    provider: 'openai'
);

$task = FlowAI::task('Write a blog post about Laravel');

// Streaming response
$streamingResponse = $agent->stream($task);

// Отримання chunk'ів по одному
while ($streamingResponse->hasMoreChunks()) {
    $chunk = $streamingResponse->getNextChunk();
    if ($chunk !== null) {
        echo $chunk; // Відправка на frontend
    }
}

// Отримання повної відповіді
$fullContent = $streamingResponse->getContent();
```

### Task Streaming Configuration

```php
$task = FlowAI::task('Explain AI concepts')
    ->stream(function($chunk, $fullContent) {
        // Callback для кожного chunk'а
        echo "Received: " . $chunk . "\n";
        echo "Total so far: " . strlen($fullContent) . " chars\n";
    });

$agent = FlowAI::agent('AI Expert', 'Explain concepts clearly');
$response = $agent->stream($task);
```

### Crew Streaming

```php
$writer = FlowAI::agent('Writer', 'Write content', 'openai');
$editor = FlowAI::agent('Editor', 'Review content', 'openai');

$crew = FlowAI::crew()
    ->addAgent($writer)
    ->addAgent($editor)
    ->addTasks([
        FlowAI::task('Write an article'),
        FlowAI::task('Review the article'),
    ]);

// Streaming crew execution
foreach ($crew->stream() as $result) {
    if ($result['is_streaming'] && !$result['is_complete']) {
        // Streaming chunk
        echo "Chunk: " . $result['chunk'] . "\n";
    } elseif ($result['is_complete']) {
        // Completed task
        echo "Task completed: " . $result['response']->getContent() . "\n";
    }
}
```

## StreamingResponse API

### Основні методи

```php
// Отримання наступного chunk'а
$chunk = $streamingResponse->getNextChunk();

// Перевірка наявності chunk'ів
$hasMore = $streamingResponse->hasMoreChunks();

// Отримання повної відповіді
$content = $streamingResponse->getContent();

// Конвертація в звичайний Response
$regularResponse = $streamingResponse->toResponse();

// Статистика
$stats = $streamingResponse->getStats();
```

### Управління буфером

```php
// Встановлення розміру буфера
$streamingResponse->setBufferSize(20);

// Отримання поточного буфера
$buffer = $streamingResponse->getBuffer();

// Очищення буфера
$streamingResponse->clearBuffer();
```

### Callback функції

```php
$streamingResponse->onChunk(function($chunk, $fullContent) {
    // Обробка кожного chunk'а
    echo "New chunk: " . $chunk . "\n";
    echo "Progress: " . strlen($fullContent) . " characters\n";
});
```

## Інтеграція з Livewire

### Компонент

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use LaraFlowAI\Facades\FlowAI;

class ChatComponent extends Component
{
    public $messages = [];
    public $currentMessage = '';
    public $isStreaming = false;
    public $streamingContent = '';

    public function sendMessage()
    {
        $this->validate(['currentMessage' => 'required|string']);
        
        // Додати повідомлення користувача
        $this->messages[] = [
            'type' => 'user',
            'content' => $this->currentMessage,
            'timestamp' => now(),
        ];

        // Створити завдання
        $task = FlowAI::task($this->currentMessage);
        $agent = FlowAI::agent('Chat Assistant', 'Help users');
        
        // Почати streaming
        $this->startStreaming($agent, $task);
        
        $this->currentMessage = '';
    }

    public function startStreaming($agent, $task)
    {
        $this->isStreaming = true;
        $this->streamingContent = '';
        
        // Додати placeholder для streaming
        $this->messages[] = [
            'type' => 'assistant',
            'content' => '',
            'is_streaming' => true,
        ];

        // Запустити streaming в фоні
        $this->dispatch('start-streaming', [
            'task' => $task->getDescription(),
        ]);
    }

    public function handleChunk($chunk)
    {
        if ($this->isStreaming) {
            $this->streamingContent .= $chunk;
            
            // Оновити останнє повідомлення
            $lastIndex = count($this->messages) - 1;
            $this->messages[$lastIndex]['content'] = $this->streamingContent;
        }
    }

    public function finishStreaming()
    {
        $this->isStreaming = false;
        $lastIndex = count($this->messages) - 1;
        $this->messages[$lastIndex]['is_streaming'] = false;
    }
}
```

### JavaScript

```javascript
document.addEventListener('livewire:init', () => {
    Livewire.on('start-streaming', (data) => {
        const eventSource = new EventSource(`/api/stream-chat?task=${encodeURIComponent(data.task)}`);
        
        eventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            
            if (data.type === 'chunk') {
                Livewire.dispatch('handleChunk', { chunk: data.chunk });
            } else if (data.type === 'complete') {
                eventSource.close();
                Livewire.dispatch('finishStreaming');
            }
        };
    });
});
```

## API Routes для Streaming

### Server-Sent Events

```php
// routes/api.php
use Illuminate\Http\StreamedResponse;
use LaraFlowAI\Facades\FlowAI;

Route::get('/stream-chat', function (Request $request) {
    $taskDescription = $request->query('task');
    
    return new StreamedResponse(function () use ($taskDescription) {
        $agent = FlowAI::agent('Chat Assistant', 'Help users');
        $task = FlowAI::task($taskDescription);
        
        $streamingResponse = $agent->stream($task);
        
        while ($streamingResponse->hasMoreChunks()) {
            $chunk = $streamingResponse->getNextChunk();
            
            if ($chunk !== null) {
                echo "data: " . json_encode([
                    'type' => 'chunk',
                    'chunk' => $chunk
                ]) . "\n\n";
                
                if (ob_get_level()) ob_flush();
                flush();
            }
        }
        
        echo "data: " . json_encode(['type' => 'complete']) . "\n\n";
        
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'Connection' => 'keep-alive',
    ]);
});
```

### WebSocket з Pusher

```php
use Pusher\Pusher;

Route::post('/stream-chat-websocket', function (Request $request) {
    $taskDescription = $request->input('task');
    $channel = $request->input('channel', 'chat-stream');
    
    $agent = FlowAI::agent('Chat Assistant', 'Help users');
    $task = FlowAI::task($taskDescription);
    
    $pusher = new Pusher(/* config */);
    
    // Запустити streaming в фоні
    dispatch(function () use ($agent, $task, $pusher, $channel) {
        $streamingResponse = $agent->stream($task);
        
        while ($streamingResponse->hasMoreChunks()) {
            $chunk = $streamingResponse->getNextChunk();
            
            if ($chunk !== null) {
                $pusher->trigger($channel, 'chunk', [
                    'chunk' => $chunk,
                    'timestamp' => now()->toISOString(),
                ]);
            }
        }
        
        $pusher->trigger($channel, 'complete', [
            'message' => 'Streaming completed',
        ]);
    });
    
    return response()->json(['status' => 'started', 'channel' => $channel]);
});
```

## Конфігурація

### Environment Variables

```env
# Увімкнути streaming
LARAFLOWAI_STREAMING_ENABLED=true

# Розмір буфера для оптимізації
LARAFLOWAI_STREAMING_BUFFER_SIZE=10

# Максимальний розмір chunk'а
LARAFLOWAI_STREAMING_MAX_CHUNK_SIZE=1000

# Timeout для streaming
LARAFLOWAI_STREAMING_TIMEOUT=300

# Затримка між chunk'ами (мілісекунди)
LARAFLOWAI_STREAMING_CHUNK_DELAY=0

# Кешування streaming відповідей
LARAFLOWAI_STREAMING_CACHE=true
LARAFLOWAI_STREAMING_CACHE_TTL=3600

# WebSocket налаштування
LARAFLOWAI_WEBSOCKET_ENABLED=false
LARAFLOWAI_WEBSOCKET_DRIVER=pusher
LARAFLOWAI_WEBSOCKET_CHANNEL_PREFIX=laraflowai

# Server-Sent Events
LARAFLOWAI_SSE_ENABLED=true
LARAFLOWAI_SSE_RETRY_TIMEOUT=3000
LARAFLOWAI_SSE_KEEP_ALIVE=30
```

### Config файл

```php
// config/laraflowai.php
'streaming' => [
    'enabled' => env('LARAFLOWAI_STREAMING_ENABLED', true),
    'default_buffer_size' => env('LARAFLOWAI_STREAMING_BUFFER_SIZE', 10),
    'max_chunk_size' => env('LARAFLOWAI_STREAMING_MAX_CHUNK_SIZE', 1000),
    'timeout' => env('LARAFLOWAI_STREAMING_TIMEOUT', 300),
    'chunk_delay' => env('LARAFLOWAI_STREAMING_CHUNK_DELAY', 0),
    'enable_caching' => env('LARAFLOWAI_STREAMING_CACHE', true),
    'cache_ttl' => env('LARAFLOWAI_STREAMING_CACHE_TTL', 3600),
],
```

## Підтримувані провайдери

Streaming підтримується нативними API наступних провайдерів:

- ✅ **OpenAI** - GPT-4, GPT-3.5-turbo
- ✅ **Anthropic** - Claude моделі
- ✅ **Grok** - Grok моделі
- ✅ **DeepSeek** - DeepSeek моделі
- ✅ **Groq** - Groq inference engine
- ✅ **Ollama** - Локальні моделі
- ⚠️ **Gemini** - Обмежена підтримка

## Оптимізація продуктивності

### Буферизація

```php
// Налаштування буфера для оптимізації
$streamingResponse->setBufferSize(20); // Збільшити буфер

// Обробка буфера
$streamingResponse->onChunk(function($chunk, $fullContent) {
    // Кешування проміжних результатів
    Cache::put("streaming_{$this->sessionId}", $fullContent, 300);
});
```

### Кешування

```php
// Кешування повних відповідей
$cacheKey = "streaming_response_" . md5($prompt);
$cached = Cache::get($cacheKey);

if ($cached) {
    // Повернути кешовану відповідь
    return $cached;
}

// Зберегти в кеш після завершення
$streamingResponse->onChunk(function($chunk, $fullContent) use ($cacheKey) {
    if ($streamingResponse->isComplete()) {
        Cache::put($cacheKey, $fullContent, 3600);
    }
});
```

## Обробка помилок

```php
try {
    $streamingResponse = $agent->stream($task);
    
    while ($streamingResponse->hasMoreChunks()) {
        $chunk = $streamingResponse->getNextChunk();
        
        if ($chunk === null) {
            // Обробка помилки або завершення
            break;
        }
        
        // Обробка chunk'а
        echo $chunk;
    }
    
} catch (\Exception $e) {
    // Обробка помилок streaming
    Log::error('Streaming error: ' . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
```

## Best Practices

1. **Використовуйте буферизацію** для оптимізації мережевих запитів
2. **Кешуйте відповіді** для повторного використання
3. **Обробляйте помилки** gracefully
4. **Встановлюйте timeout'и** для запобігання зависання
5. **Використовуйте WebSocket** для real-time додатків
6. **Моніторьте продуктивність** streaming операцій

## Приклади використання

Дивіться файли в `examples/` директорії:
- `streaming-usage.php` - Базові приклади
- `livewire-streaming-example.php` - Livewire інтеграція
