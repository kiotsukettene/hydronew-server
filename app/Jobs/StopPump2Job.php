<?php

namespace App\Jobs;

use App\Models\Device;
use App\Services\FiltrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StopPump2Job implements ShouldQueue
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
     * Execute the job - automatically stop pump 2 after target liters reached.
     */
    public function handle(FiltrationService $filtrationService): void
    {
        Log::info('StopPump2Job: Executing auto-stop for pump 2', [
            'device_id' => $this->deviceId
        ]);

        try {
            $device = Device::find($this->deviceId);
            
            if (!$device) {
                Log::warning('StopPump2Job: Device not found', [
                    'device_id' => $this->deviceId
                ]);
                return;
            }

            $pumpState = \App\Models\HydroponicPumpState::where('device_id', $device->id)->first();
            
            if (!$pumpState) {
                Log::warning('StopPump2Job: Pump state not found', [
                    'device_id' => $this->deviceId
                ]);
                return;
            }

            // Only stop if pump is still running
            if (!$pumpState->pump_2_state) {
                Log::info('StopPump2Job: Pump 2 already stopped, skipping', [
                    'device_id' => $this->deviceId
                ]);
                return;
            }

            // Send CLOSE command to stop pump 2
            $filtrationService->publishCommand(
                "hydroponics/{$device->serial_number}/pump/2",
                'CLOSE'
            );

            Log::info('StopPump2Job: Auto-stop command sent for pump 2', [
                'device_id' => $this->deviceId,
                'device_serial' => $device->serial_number,
                'target_liters' => $pumpState->pump_2_target_liters,
                'started_at' => $pumpState->pump_2_started_at
            ]);

        } catch (\Exception $e) {
            Log::error('StopPump2Job: Failed to auto-stop pump 2', [
                'device_id' => $this->deviceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
