<?php

namespace UspsShipping\Laravel\Commands;

use Illuminate\Console\Command;
use UspsShipping\Laravel\Models\UspsRateCache;

class CleanupCacheCommand extends Command
{
    protected $signature = 'usps:cleanup-cache';
    protected $description = 'Clean up expired USPS rate cache entries';

    public function handle()
    {
        $deleted = UspsRateCache::clearExpired();

        $this->info("Deleted {$deleted} expired cache entries");

        // Show cache statistics
        $totalCached = UspsRateCache::count();
        $this->line("Remaining cache entries: {$totalCached}");

        return 0;
    }
}
