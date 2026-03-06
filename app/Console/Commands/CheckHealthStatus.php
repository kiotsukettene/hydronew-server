<?php

namespace App\Console\Commands;

use App\Models\HydroponicSetup;
use App\Services\HealthStatusService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckHealthStatus extends Command
{
    protected $signature = 'hydroponics:check-health-status';
    protected $description = 'Check and update health status for all active hydroponic setups based on sensor readings';

    private HealthStatusService $healthStatusService;
    private NotificationService $notificationService;

    public function __construct(
        HealthStatusService $healthStatusService,
        NotificationService $notificationService
    ) {
        parent::__construct();
        $this->healthStatusService = $healthStatusService;
        $this->notificationService = $notificationService;
    }

    public function handle(): int
    {
        $this->info('Checking health status for all active setups...');

        $setups = HydroponicSetup::where('status', 'active')
            ->where('harvest_status', '!=', 'harvested')
            ->where('is_archived', false)
            ->get();

        $totalSetups = $setups->count();
        $updatedCount = 0;
        $statusCounts = [
            'good' => 0,
            'moderate' => 0,
            'poor' => 0,
        ];

        foreach ($setups as $setup) {
            $oldStatus = $setup->health_status;
            $newStatus = $this->healthStatusService->calculateHealthStatus($setup);

            // Track status counts
            $statusCounts[$newStatus] = ($statusCounts[$newStatus] ?? 0) + 1;

            if ($oldStatus !== $newStatus) {
                $setup->update(['health_status' => $newStatus]);
                
                // Send notification if status changed to poor
                if ($newStatus === 'poor') {
                    $this->notificationService->notifyHealthStatusChange($setup, $oldStatus, $newStatus);
                }
                
                $this->line("Setup #{$setup->id} ({$setup->crop_name}): {$oldStatus} → {$newStatus}");
                $updatedCount++;

                Log::info('Health status updated by scheduled task', [
                    'setup_id' => $setup->id,
                    'crop_name' => $setup->crop_name,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]);
            }
        }

        $this->info("Health status check completed.");
        $this->info("Total setups checked: {$totalSetups}");
        $this->info("Setups updated: {$updatedCount}");
        $this->info("Status distribution - Good: {$statusCounts['good']}, Moderate: {$statusCounts['moderate']}, Poor: {$statusCounts['poor']}");

        Log::info('Health status check completed', [
            'total_setups' => $totalSetups,
            'updated_count' => $updatedCount,
            'status_distribution' => $statusCounts,
        ]);

        return Command::SUCCESS;
    }
}
