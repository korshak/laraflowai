<?php

namespace LaraFlowAI\Console\Commands;

use Illuminate\Console\Command;
use LaraFlowAI\Memory\MemoryManager;

class CleanupMemoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'laraflowai:cleanup-memory {--days=90 : Number of days to keep memory data}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up expired memory data from LaraFlowAI';

    /**
     * Execute the console command.
     */
    public function handle(MemoryManager $memory): int
    {
        $days = (int) $this->option('days');
        
        $this->info("Cleaning up memory data older than {$days} days...");
        
        $deleted = $memory->cleanup();
        
        $this->info("Cleaned up {$deleted} expired memory records.");
        
        return Command::SUCCESS;
    }
}
