<?php

declare(strict_types=1);

namespace LaraFlowAI\MCP\Constants;

class MCPMethods
{
    // Core MCP methods
    public const INITIALIZE = 'initialize';
    public const INITIALIZED = 'initialized';
    public const PING = 'ping';
    public const PONG = 'pong';

    // Tools
    public const TOOLS_LIST = 'tools/list';
    public const TOOLS_CALL = 'tools/call';

    // Resources
    public const RESOURCES_LIST = 'resources/list';
    public const RESOURCES_READ = 'resources/read';
    public const RESOURCES_SUBSCRIBE = 'resources/subscribe';
    public const RESOURCES_UNSUBSCRIBE = 'resources/unsubscribe';

    // Prompts
    public const PROMPTS_LIST = 'prompts/list';
    public const PROMPTS_GET = 'prompts/get';

    // Samples
    public const SAMPLES_LIST = 'samples/list';
    public const SAMPLES_GET = 'samples/get';

    // Logging
    public const LOGGING_SET_LEVEL = 'logging/setLevel';

    // Notifications
    public const NOTIFICATIONS_INITIALIZED = 'notifications/initialized';
    public const NOTIFICATIONS_TOOLS_LIST_CHANGED = 'notifications/tools/list_changed';
    public const NOTIFICATIONS_RESOURCES_LIST_CHANGED = 'notifications/resources/list_changed';
    public const NOTIFICATIONS_PROMPTS_LIST_CHANGED = 'notifications/prompts/list_changed';
    public const NOTIFICATIONS_SAMPLES_LIST_CHANGED = 'notifications/samples/list_changed';

    public static function getAllMethods(): array
    {
        return [
            self::INITIALIZE,
            self::INITIALIZED,
            self::PING,
            self::PONG,
            self::TOOLS_LIST,
            self::TOOLS_CALL,
            self::RESOURCES_LIST,
            self::RESOURCES_READ,
            self::RESOURCES_SUBSCRIBE,
            self::RESOURCES_UNSUBSCRIBE,
            self::PROMPTS_LIST,
            self::PROMPTS_GET,
            self::SAMPLES_LIST,
            self::SAMPLES_GET,
            self::LOGGING_SET_LEVEL,
            self::NOTIFICATIONS_INITIALIZED,
            self::NOTIFICATIONS_TOOLS_LIST_CHANGED,
            self::NOTIFICATIONS_RESOURCES_LIST_CHANGED,
            self::NOTIFICATIONS_PROMPTS_LIST_CHANGED,
            self::NOTIFICATIONS_SAMPLES_LIST_CHANGED,
        ];
    }

    public static function isNotification(string $method): bool
    {
        return str_starts_with($method, 'notifications/');
    }

    public static function isRequest(string $method): bool
    {
        return !self::isNotification($method);
    }
}
