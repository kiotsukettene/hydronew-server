<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewSensorReadingsAvailable implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // This public property will be the data sent to Pusher
    public array $readings;

    /**
     * Create a new event instance.
     * @param array $readings The new readings that were just saved
     */
    public function __construct(array $readings)
    {
        $this->readings = $readings;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [new Channel('sensor-data')];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        // Your frontend/app will listen for this event name
        return 'new-readings-batch';
    }
}
