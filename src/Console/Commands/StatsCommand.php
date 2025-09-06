<?php

namespace LaraFlowAI\Console\Commands;

use Illuminate\Console\Command;
use LaraFlowAI\Metrics\TokenUsageTracker;
use LaraFlowAI\Memory\MemoryManager;

class StatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'laraflowai:stats {--days=30 : Number of days to show stats for}';

    /**
     * The console command description.
     */
    protected $description = 'Show LaraFlowAI usage statistics';

    /**
     * Execute the console command.
     */
    public function handle(TokenUsageTracker $tracker, MemoryManager $memory): int
    {
        $days = (int) $this->option('days');
        
        $this->info("LaraFlowAI Statistics (Last {$days} days)");
        $this->newLine();
        
        // Token usage stats
        $summary = $tracker->getSummary();
        $this->info('Token Usage:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Monthly Tokens', number_format($summary['monthly_tokens'])],
                ['Monthly Requests', number_format($summary['monthly_requests'])],
                ['Avg Tokens/Request', number_format($summary['avg_tokens_per_request'])],
            ]
        );
        
        // Memory stats
        $memoryStats = $memory->getStats();
        $this->newLine();
        $this->info('Memory Usage:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Memories', number_format($memoryStats['total'])],
                ['Active Memories', number_format($memoryStats['active'])],
                ['Expired Memories', number_format($memoryStats['expired'])],
            ]
        );
        
        // Provider breakdown
        $providerStats = $tracker->getStats(null, null, $days);
        if (!empty($providerStats)) {
            $this->newLine();
            $this->info('Provider Breakdown:');
            $this->table(
                ['Provider', 'Model', 'Tokens', 'Requests'],
                array_map(function ($stat) {
                    return [
                        $stat->provider,
                        $stat->model,
                        number_format($stat->total_tokens),
                        number_format($stat->request_count),
                    ];
                }, $providerStats)
            );
        }
        
        return Command::SUCCESS;
    }
}
