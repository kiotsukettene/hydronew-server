<?php

namespace App\Http\Controllers\Notification;

use App\Events\NotificationBroadcast;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Notification\NotificationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = Notification::where('user_id', $user->id)->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'user_id' => $n->user_id,
                'device_id' => $n->device_id,
                'title' => $n->title,
                'message' => $n->message,
                'type' => $n->type,
                'is_read' => $n->is_read,
                'created_at' => $n->created_at,
                'time' => date('h:i A', strtotime($n->created_at)),
            ];
        });
        return response()->json(['data' => $notifications]);
    }

    public function getUnreadCount()
    {
        $user = Auth::user();
        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $unreadCount]);
    }

    public function createNotification(NotificationRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = Auth::id();
        $notification = Notification::create($validated);

        Log::info('Notification created', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'title' => $notification->title
        ]);

        // Format the notification with time before broadcasting
        $notificationData = $notification->toArray();
        $notificationData['time'] = date('h:i A', strtotime($notification->created_at));

        // Broadcast to all user connections (not just others)
        Log::info('Broadcasting notification to channel', [
            'channel' => 'user.' . $notification->user_id,
            'event' => 'notification.created'
        ]);

        broadcast(new NotificationBroadcast($notification));

        Log::info('Notification broadcast dispatched successfully');

        return response()->json
        (
            [
            'message' => 'Notification created',
            'data' => $notificationData
        ], 201
        );
    }

    public function markAsRead($id)
    {
        $userId = Auth::id();

        $notification = Notification::where('id',$id)-> where('user_id', $userId)->first();

        if (!$notification)
        {
            return response() ->json
            (
                [
                    'message' => 'Notification not found'
                ], 404
            );
        }

        $notification->is_read = true;
        $notification->save();

        return response()->json
        (
            [
                'message' => 'Notification marked as read',
                'data' => $notification
            ], 200
        );

    }
      public function markAllAsRead()
    {
        $userId = Auth::id();

        Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(
            [
                'message' => 'All notifications marked as read'
            ], 200
        );
    }
}
