<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\FiltrationProcess;
use App\Services\FiltrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CloseValve1Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $deviceId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $deviceId)
    {
        $this->deviceId = $deviceId;
    }

    /**
     * Execute the job - automatically close valve 1 after 24 minutes if still open.
     */
    public function handle(FiltrationService $filtrationService): void
    {
        Log::info('CloseValve1Job: Executing auto-close for valve 1', [
            'device_id' => $this->deviceId
        ]);

        try {
            $device = Device::find($this->deviceId);
            
            if (!$device) {
                Log::warning('CloseValve1Job: Device not found', [
                    'device_id' => $this->deviceId
                ]);
                return;
            }

            $filtrationProcess = FiltrationProcess::where('device_id', $device->id)
                ->whereIn('status', ['active', 'paused'])
                ->first();
            
            if (!$filtrationProcess) {
                Log::info('CloseValve1Job: No active or paused filtration process found', [
                    'device_id' => $this->deviceId
                ]);
                return;
            }

            // Only close if valve 1 is still open
            if (!$filtrationProcess->valve_1_state) {
                Log::info('CloseValve1Job: Valve 1 already closed, skipping', [
                    'device_id' => $this->deviceId
                ]);
                return;
            }

            // Send CLOSE command to valve 1
            $filtrationService->publishCommand(
                "mfc/{$device->serial_number}/valve/1",
                'CLOSE'
            );

            Log::info('CloseValve1Job: Auto-close command sent for valve 1', [
                'device_id' => $this->deviceId,
                'device_serial' => $device->serial_number
            ]);

        } catch (\Exception $e) {
            Log::error('CloseValve1Job: Failed to auto-close valve 1', [
                'device_id' => $this->deviceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
