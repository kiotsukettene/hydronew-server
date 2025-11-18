<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;


    /**
     * Create a new event instance.
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->notification->user_id);
    }

    public function broadcastAs()
    {
        return 'notification.created';
    }

    public function broadcastWith()
    {
        return [
            'notification' => [
                'id' => $this->notification->id,
                'user_id' => $this->notification->user_id,
                'device_id' => $this->notification->device_id,
                'title' => $this->notification->title,
                'message' => $this->notification->message,
                'type' => $this->notification->type,
                'is_read' => $this->notification->is_read,
                'created_at' => $this->notification->created_at,
                'time' => date('h:i A', strtotime($this->notification->created_at)),
            ]
        ];
    }
}
