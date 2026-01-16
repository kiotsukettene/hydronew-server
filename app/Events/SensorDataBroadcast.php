<?php

namespace App\Events;

use App\Models\SensorReading;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SensorDataBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sensorReading;
    public $deviceId;
    public $systemType;

    /**
     * Create a new event instance.
     */
    public function __construct(SensorReading $sensorReading, int $deviceId, string $systemType)
    {
        $this->sensorReading = $sensorReading;
        $this->deviceId = $deviceId;
        $this->systemType = $systemType;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Broadcast to device-specific channel
            new Channel('sensor.device.' . $this->deviceId),
            
            // Also broadcast to system-specific channel (optional)
            new Channel('sensor.device.' . $this->deviceId . '.' . $this->systemType),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'sensor.data.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'device_id' => $this->deviceId,
            'sensor_system_id' => $this->sensorReading->sensor_system_id,
            'system_type' => $this->systemType,
            'readings' => [
                'ph' => $this->sensorReading->ph,
                'tds' => $this->sensorReading->tds,
                'turbidity' => $this->sensorReading->turbidity,
                'water_level' => $this->sensorReading->water_level,
                'humidity' => $this->sensorReading->humidity,
                'temperature' => $this->sensorReading->temperature,
                'ec' => $this->sensorReading->ec,
                'electric_current' => $this->sensorReading->electric_current,
            ],
            'reading_time' => $this->sensorReading->reading_time?->toIso8601String(),
            'timestamp' => now()->timestamp,
        ];
    }
}