<?php

namespace LaraFlowAI\Console\Commands;

use Illuminate\Console\Command;
use LaraFlowAI\Metrics\TokenUsageTracker;

class CleanupTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'laraflowai:cleanup-tokens {--days=90 : Number of days to keep token usage data}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old token usage data from LaraFlowAI';

    /**
     * Execute the console command.
     */
    public function handle(TokenUsageTracker $tracker): int
    {
        $days = (int) $this->option('days');
        
        $this->info("Cleaning up token usage data older than {$days} days...");
        
        $deleted = $tracker->cleanup($days);
        
        $this->info("Cleaned up {$deleted} old token usage records.");
        
        return Command::SUCCESS;
    }
}
