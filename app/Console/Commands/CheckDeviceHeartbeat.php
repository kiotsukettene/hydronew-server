<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\FiltrationService;
use Illuminate\Console\Command;

class CheckDeviceHeartbeat extends Command
{
    protected $signature = 'device:check-heartbeat
                            {--timeout=90 : Seconds without heartbeat to consider device offline}';
    protected $description = 'Mark devices offline when heartbeat has not been received within timeout; pause treatment if valve 1 open and dirty water > 6%';

    public function __construct(
        protected FiltrationService $filtrationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $timeoutSeconds = (int) $this->option('timeout');
        $threshold = now()->subSeconds($timeoutSeconds);

        $devices = Device::where('status', 'online')
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_heartbeat_at')
                    ->orWhere('last_heartbeat_at', '<', $threshold);
            })
            ->get();

        foreach ($devices as $device) {
            $this->info("Device {$device->serial_number} (id={$device->id}) marked offline (no heartbeat since " . ($device->last_heartbeat_at?->toDateTimeString() ?? 'never') . ')');
            $device->update(['status' => 'offline']);
            $this->filtrationService->onDeviceOffline($device);
        }

        return Command::SUCCESS;
    }
}
