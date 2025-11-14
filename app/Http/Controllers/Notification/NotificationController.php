<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Notification\NotificationRequest;
use Illuminate\Support\Facades\Auth;
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

    public function createNotification(NotificationRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = Auth::id();
        $notification = Notification::create($validated);

        return response()->json
        (
            [
            'message' => 'Notification created', 
            'data' => $notification
        ], 201
        );
    }

    public function markAsRead($id)
    {
        $userId = Auth::id();

        $notification = Notification::where('id',$id)-> where('user_id', $userId)->first();

        if (!$notification)
        {
            return response ->json
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
}
