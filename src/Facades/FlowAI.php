<?php

namespace LaraFlowAI\Facades;

use Illuminate\Support\Facades\Facade;
use LaraFlowAI\Contracts\ProviderContract;
use LaraFlowAI\Agent;
use LaraFlowAI\Task;
use LaraFlowAI\Crew;
use LaraFlowAI\Flow;
use LaraFlowAI\Memory\MemoryManager;

/**
 * @method static ProviderContract llm(string $driver = null)
 * @method static Agent agent(string $role, string $goal, string $provider = null)
 * @method static Task task(string $description, array $config = [])
 * @method static Crew crew(array $config = [])
 * @method static Flow flow(array $config = [])
 * @method static MemoryManager memory()
 * @method static void extend(string $driver, callable $resolver)
 * @method static array getAvailableProviders()
 * @method static bool hasProvider(string $driver)
 * @method static string getDefaultProvider()
 * @method static void setDefaultProvider(string $provider)
 */
class FlowAI extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laraflowai';
    }
}
