<?php

namespace LaraFlowAI\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use LaraFlowAI\LaraFlowAIServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaraFlowAIServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup LaraFlowAI config
        $app['config']->set('laraflowai', [
            'default_provider' => 'openai',
            'providers' => [
                'openai' => [
                    'driver' => \LaraFlowAI\Providers\OpenAIProvider::class,
                    'api_key' => 'test-key',
                    'model' => 'gpt-3.5-turbo',
                ],
            ],
            'memory' => [
                'driver' => 'database',
                'table' => 'laraflowai_memory',
                'cache_ttl' => 3600,
            ],
        ]);
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
