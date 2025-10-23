<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\SensorReading;
use App\Models\Sensor; // <<< --- THIS IS THE CRITICAL FIX

class SensorReadingsReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The new sensor reading.
     */
    public SensorReading $reading;

    /**
     * Create a new event instance.
     * We use `load('sensor')` to pre-package the sensor data.
     */
    public function __construct(SensorReading $reading)
    {
        $this->reading = $reading->load('sensor');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // This is your main Pusher channel
        return [new Channel('sensor-data')];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        // This is the event name your frontend will listen for
        return 'new-reading';
    }

    /**
     * Get the data to broadcast.
     * This method is more robust than sending the whole model.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        // We send the data as a plain array.
        // This avoids many serialization issues.
        return [
            'reading' => $this->reading->toArray(),
        ];
    }
}

