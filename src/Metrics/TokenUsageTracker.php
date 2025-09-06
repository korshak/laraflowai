<?php

namespace LaraFlowAI\Metrics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * TokenUsageTracker class tracks token usage for LLM providers.
 * 
 * This class provides functionality to track token usage and generate
 * statistics for LLM provider usage. It stores data in a database
 * table and provides various reporting methods.
 * 
 * @package LaraFlowAI\Metrics
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class TokenUsageTracker
{
    /**
     * The database table name for token usage tracking.
     * 
     * @var string
     */
    protected string $table = 'laraflowai_token_usage';

    /**
     * Track token usage for a request.
     * 
     * @param string $provider The provider name
     * @param string $model The model name
     * @param int $promptTokens Number of prompt tokens
     * @param int $completionTokens Number of completion tokens
     * @param float|null $cost The cost of the request (deprecated, always null)
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function track(
        string $provider,
        string $model,
        int $promptTokens,
        int $completionTokens,
        float $cost = null,
        array $metadata = []
    ): void {
        $totalTokens = $promptTokens + $completionTokens;
        
        $data = [
            'provider' => $provider,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
        ];

        // Store in database
        DB::table($this->table)->insert($data);

        // Update cache for quick access
        $this->updateCache($provider, $model, $totalTokens, null);
    }

    /**
     * Get usage statistics
     */
    public function getStats(string $provider = null, string $model = null, int $days = 30): array
    {
        $query = DB::table($this->table)
            ->where('created_at', '>=', now()->subDays($days));

        if ($provider) {
            $query->where('provider', $provider);
        }

        if ($model) {
            $query->where('model', $model);
        }

        $stats = $query->selectRaw('
            provider,
            model,
            SUM(prompt_tokens) as total_prompt_tokens,
            SUM(completion_tokens) as total_completion_tokens,
            SUM(total_tokens) as total_tokens,
            COUNT(*) as request_count,
            AVG(total_tokens) as avg_tokens_per_request
        ')->groupBy('provider', 'model')->get();

        return $stats->toArray();
    }

    /**
     * Get daily usage for a period
     */
    public function getDailyUsage(string $provider = null, int $days = 30): array
    {
        $query = DB::table($this->table)
            ->where('created_at', '>=', now()->subDays($days));

        if ($provider) {
            $query->where('provider', $provider);
        }

        $usage = $query->selectRaw('
            DATE(created_at) as date,
            provider,
            SUM(total_tokens) as total_tokens,
            COUNT(*) as request_count
        ')->groupBy('date', 'provider')->orderBy('date')->get();

        return $usage->toArray();
    }

    /**
     * Get current month usage
     */
    public function getCurrentMonthUsage(): array
    {
        $cacheKey = 'laraflowai_monthly_usage_' . now()->format('Y-m');
        
        return Cache::remember($cacheKey, 3600, function () {
            return $this->getStats(null, null, 30);
        });
    }

    /**
     * Get provider costs (deprecated - always returns empty array)
     * 
     * @deprecated Cost tracking has been removed
     */
    public function getProviderCosts(int $days = 30): array
    {
        // Cost tracking has been removed
        return [];
    }

    /**
     * Update cache with usage data
     */
    protected function updateCache(string $provider, string $model, int $tokens, float $cost = null): void
    {
        $cacheKey = "laraflowai_usage_{$provider}_{$model}";
        $current = Cache::get($cacheKey, [
            'total_tokens' => 0,
            'request_count' => 0
        ]);

        $current['total_tokens'] += $tokens;
        $current['request_count'] += 1;

        Cache::put($cacheKey, $current, 86400); // 24 hours
    }

    /**
     * Clean up old usage data
     */
    public function cleanup(int $days = 90): int
    {
        return DB::table($this->table)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Get usage summary
     */
    public function getSummary(): array
    {
        $monthly = $this->getCurrentMonthUsage();
        $totalTokens = array_sum(array_column($monthly, 'total_tokens'));
        $totalRequests = array_sum(array_column($monthly, 'request_count'));

        return [
            'monthly_tokens' => $totalTokens,
            'monthly_requests' => $totalRequests,
            'avg_tokens_per_request' => $totalRequests > 0 ? $totalTokens / $totalRequests : 0,
            'providers' => array_unique(array_column($monthly, 'provider')),
        ];
    }
}
