<?php

namespace App\Http\Controllers;

use App\Http\Requests\Feedback\StoreFeedbackRequest;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the authenticated user's feedback.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);
        $deviceId = $request->input('device_id'); // Optional filter

        $query = Feedback::where('user_id', $user->id)
            ->with('device:id,device_name,serial_number')
            ->orderBy('created_at', 'desc');

        // Apply device filter if provided
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $feedback = $query->skip($offset)
            ->take($limit)
            ->get();

        $total = Feedback::where('user_id', $user->id)
            ->when($deviceId, function ($q) use ($deviceId) {
                return $q->where('device_id', $deviceId);
            })
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => $feedback,
            'has_more' => ($offset + $limit) < $total,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
        ]);
    }

    /**
     * Store a newly created feedback in storage.
     */
    public function store(StoreFeedbackRequest $request)
    {
        $user = Auth::user();
        
        // Get the user's paired device
        $device = $user->devices()->where('devices.is_archived', false)->first();
        
        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'No device found. Please pair a device before submitting feedback.',
            ], 400);
        }

        $validated = $request->validated();
        $validated['user_id'] = $user->id;
        $validated['device_id'] = $device->id;

        $feedback = Feedback::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Feedback submitted successfully. We appreciate your input!',
            'data' => $feedback,
        ], 201);
    }
}
