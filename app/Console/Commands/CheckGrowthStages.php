<?php

namespace App\Console\Commands;

use App\Models\HydroponicSetup;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckGrowthStages extends Command
{
    protected $signature = 'growth:check';
    protected $description = 'Check and update growth stages for all active hydroponic setups';

    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle(): int
    {
        $this->info('Checking growth stages for all active setups...');

        $setups = HydroponicSetup::where('status', 'active')
            ->where('harvest_status', '!=', 'harvested')
            ->where('is_archived', false)
            ->get();

        $updatedCount = 0;
        $now = Carbon::now();

        foreach ($setups as $setup) {
            $setupDate = Carbon::parse($setup->setup_date);
            $plantAge = (int) $setupDate->diffInDays($now);

            $newGrowthStage = $this->calculateGrowthStage($plantAge, $setup->harvest_date, $now);
            $oldStage = $setup->growth_stage;

            if ($oldStage !== $newGrowthStage) {
                $setup->update(['growth_stage' => $newGrowthStage]);
                
                $this->notificationService->notifyGrowthStageChange($setup, $oldStage, $newGrowthStage);
                
                $this->line("Setup #{$setup->id} ({$setup->crop_name}): {$oldStage} → {$newGrowthStage}");
                $updatedCount++;

                Log::info('Growth stage updated by scheduled task', [
                    'setup_id' => $setup->id,
                    'crop_name' => $setup->crop_name,
                    'old_stage' => $oldStage,
                    'new_stage' => $newGrowthStage,
                ]);
            }
        }

        $this->info("Growth stage check completed. {$updatedCount} setups updated.");

        return Command::SUCCESS;
    }

    private function calculateGrowthStage(int $plantAge, $harvestDate, Carbon $now): string
    {
        // If no harvest date is set, use age-based stages only
        if (!$harvestDate) {
            if ($plantAge < 14) {
                return 'seedling';
            } elseif ($plantAge < 30) {
                return 'vegetative';
            } else {
                return 'flowering';
            }
        }

        $harvestDate = Carbon::parse($harvestDate);
        $daysUntilHarvest = $now->diffInDays($harvestDate, false);

        // Check if overgrown (5+ days past harvest date)
        if ($daysUntilHarvest < -5) {
            return 'overgrown';
        }

        // Check if harvest-ready (at or past harvest date, but within 5 days)
        if ($daysUntilHarvest <= 0) {
            return 'harvest-ready';
        }

        // Age-based stages before harvest date
        if ($plantAge < 14) {
            return 'seedling';
        } elseif ($plantAge < 30) {
            return 'vegetative';
        } else {
            return 'flowering';
        }
    }
}
