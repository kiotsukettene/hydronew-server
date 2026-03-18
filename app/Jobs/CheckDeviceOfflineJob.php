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

/**
 * Dispatched 60 seconds after a heartbeat is received.
 * IoT publishes heartbeat every 45 seconds, so 60s timeout gives 15s buffer.
 * If no newer heartbeat arrived, marks the device offline and pauses treatment if needed (valve 1 open, dirty water > 6%).
 */
class CheckDeviceOfflineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $deviceSerial
    ) {}

    public function handle(FiltrationService $filtrationService): void
    {
        $device = Device::where('serial_number', $this->deviceSerial)->first();
        if (!$device) {
            return;
        }

        $device->refresh();
        $threshold = now()->subSeconds(60);
        $lastHeartbeat = $device->last_heartbeat_at;

        if ($lastHeartbeat !== null && $lastHeartbeat->gte($threshold)) {
            // A heartbeat arrived in the last 60 seconds – still online, do nothing
            return;
        }

        if ($device->status === 'offline') {
            return;
        }

        Log::info('CheckDeviceOfflineJob: Marking device offline (no heartbeat within 60s)', [
            'serial' => $this->deviceSerial,
            'last_heartbeat_at' => $lastHeartbeat?->toDateTimeString(),
        ]);

        $device->update(['status' => 'offline']);
        $filtrationService->onDeviceOffline($device);
    }
}
