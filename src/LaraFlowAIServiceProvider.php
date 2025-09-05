<?php

namespace LaraFlowAI;

use Illuminate\Support\ServiceProvider;
use LaraFlowAI\LLMFactory;
use LaraFlowAI\Memory\MemoryManager;
use LaraFlowAI\Contracts\MemoryContract;
use LaraFlowAI\MCP\MCPClient;

class LaraFlowAIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the main service
        $this->app->singleton('laraflowai', function ($app) {
            return new LaraFlowAIManager($app);
        });

        // Register LLM Factory
        $this->app->singleton('laraflowai.llm', function ($app) {
            $defaultProvider = $app['config']['laraflowai.default_provider'] ?? 'openai';
            return new LLMFactory($app, $defaultProvider);
        });

        // Register Memory Manager
        $this->app->singleton(MemoryContract::class, function ($app) {
            return new MemoryManager();
        });

        // Register memory manager with alias
        $this->app->alias(MemoryContract::class, 'laraflowai.memory');

        // Register MCP Client
        $this->app->singleton('laraflowai.mcp', function ($app) {
            $mcpConfig = $app['config']['laraflowai.mcp'] ?? [];
            return new MCPClient($mcpConfig);
        });

        // Register MCP Client with alias
        $this->app->alias('laraflowai.mcp', MCPClient::class);

        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/../config/laraflowai.php', 'laraflowai');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config and migrations
        $this->publishes([
            __DIR__ . '/../config/laraflowai.php' => config_path('laraflowai.php'),
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'laraflowai');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \LaraFlowAI\Console\Commands\CleanupMemoryCommand::class,
                \LaraFlowAI\Console\Commands\CleanupTokensCommand::class,
                \LaraFlowAI\Console\Commands\StatsCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'laraflowai',
            'laraflowai.llm',
            MemoryContract::class,
            'laraflowai.memory',
            'laraflowai.mcp',
            MCPClient::class,
        ];
    }
}
