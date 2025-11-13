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
        $notifications = Notification::where('user_id', $user->id)->get();

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
}
