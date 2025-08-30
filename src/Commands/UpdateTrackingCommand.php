<?php

namespace UspsShipping\Laravel\Commands;

use Illuminate\Console\Command;
use UspsShipping\Laravel\Services\TrackingService;
use UspsShipping\Laravel\Models\UspsShipment;

class UpdateTrackingCommand extends Command
{
    protected $signature = 'usps:update-tracking 
                            {--tracking=* : Specific tracking numbers to update}
                            {--all : Update all non-delivered shipments}
                            {--days=7 : Update shipments from last N days}';

    protected $description = 'Update USPS tracking information for shipments';

    public function handle(TrackingService $trackingService)
    {
        if ($this->option('all')) {
            $this->info('Updating all non-delivered shipments...');
            $updated = $trackingService->updateMultipleShipments();
            $this->info("Updated {$updated} shipments");
            return 0;
        }

        if ($trackingNumbers = $this->option('tracking')) {
            $this->info('Updating specific tracking numbers...');
            $updated = $trackingService->updateMultipleShipments($trackingNumbers);
            $this->info("Updated {$updated} of " . count($trackingNumbers) . " requested shipments");
            return 0;
        }

        // Update shipments from last N days
        $days = (int) $this->option('days');
        $shipments = UspsShipment::where('created_at', '>=', now()->subDays($days))
            ->whereNotIn('status', ['delivered', 'returned'])
            ->pluck('tracking_number')
            ->toArray();

        if (empty($shipments)) {
            $this->info('No shipments to update');
            return 0;
        }

        $this->info("Updating " . count($shipments) . " shipments from last {$days} days...");

        $progressBar = $this->output->createProgressBar(count($shipments));
        $progressBar->start();

        $updated = 0;
        foreach ($shipments as $trackingNumber) {
            if ($trackingService->updateShipmentTracking($trackingNumber)) {
                $updated++;
            }
            $progressBar->advance();
            usleep(250000); // Rate limiting
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Updated {$updated} shipments");

        return 0;
    }
}
