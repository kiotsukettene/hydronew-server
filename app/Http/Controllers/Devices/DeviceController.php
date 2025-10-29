<?php

namespace App\Http\Controllers\Devices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;

class DeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Find all the devices
        $user = $request->user();

        $devices = $user->devices()->get();

        if ($devices->isEmpty()) {
            return response()->json(['message' => 'No devices found.'], 404);
        }

        return response()->json(['devices' => $devices], 200);
    }

    /**
     * Connect a device to the authenticated user.
     */
    public function connectDevice(Request $request)
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|exists:devices,serial_number',
        ]);

        $user = $request->user();

        // Find the device by serial number
        $device = Device::where('serial_number', $validated['serial_number'])->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found.'], 404);
        }

        // If already connected to another user AND status is "connected"
        if ($device->status === 'connected' && $device->user_id !== $user->id) {
            return response()->json(['message' => 'Device is already connected to another user.'], 409);
        }

        // If already connected to this user
        if ($device->status === 'connected') {
            return response()->json(['message' => 'Device is already connected to your account.'], 200);
        }

        // If status is not connected, connect it to this user
        $device->update([
            'user_id' => $user->id,
            'status' => 'connected',
        ]);

        return response()->json([
            'message' => 'Device connected successfully.',
            'device' => $device,
        ], 200);
    }

    public function disconnectDevice(Request $request, Device $device)
    {
        $user = $request->user();

        // Optional: make sure the device belongs to the authenticated user
        if ($device->user_id !== $user->id) {
            return response()->json(['message' => 'You are not authorized to disconnect this device.'], 403);
        }

        // Update the status
        $device->update(['status' => 'not connected']);

        return response()->json([
            'message' => 'Device disconnected successfully.',
            'device' => $device
        ], 200);
    }
}
