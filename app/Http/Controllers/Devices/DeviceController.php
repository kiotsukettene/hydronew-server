<?php

namespace App\Http\Controllers\Devices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PairingToken;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function pairingToken (Request $request)
    {
        // Validate the request data
        $user = $request->user();

        PairingToken::where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '<', now())
            ->delete();

        $plainToken = Str::random(32);
        $tokenHash = hash('sha256', $plainToken);
        PairingToken::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addMinutes(10),
        ]);

        return response()->json([
            'pairing_token' => $plainToken,
            'expires_at' => now()->addMinutes(10)->toDateTimeString(),
        ]);

    }

    public function provision(Request $request)
    {
        $validated = $request->validate([
            'serial_number'    => 'required|string|exists:devices,serial_number',
            'pairing_token'    => 'required|string|exists:pairing_tokens,token_hash',
            'machine_name'     => 'nullable|string',
            'model'            => 'nullable|string',
            'firmware_version' => 'nullable|string',
        ]);

        $serialNumber = $validated['serial_number'];
        $pairingTokenPlain = $validated['pairing_token'];

        // Hash pairing token the same way it was stored
        $tokenHash = hash('sha256', $pairingTokenPlain);

        $pairingToken = PairingToken::where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$pairingToken) {
            return response()->json(['message' => 'Invalid or expired pairing token'], 401);
        }

        // Find the device
        $device = Device::where('serial_number', $serialNumber)->first();

        if (!$device) {
            return response()->json(['message' => 'No device found with this serial number'], 404);
        }

        // Multi-user support: link user to device
        DeviceUser::updateOrCreate(
            [
                'device_id' => $device->id,
                'user_id'   => $pairingToken->user_id
            ],
            [
                'token'      => $tokenHash,
                'expires_at' => now()->addMinutes(10)
            ]
        );

        // Mark token as used
        $pairingToken->update(['used_at' => now()]);

        // Update device info if new data is provided
        $device->update([
            'device_name'      => $validated['machine_name'] ?? $device->device_name,
            'model'            => $validated['model'] ?? $device->model,
            'firmware_version' => $validated['firmware_version'] ?? $device->firmware_version,
            'status'           => 'connected'
        ]);

        return response()->json([
            'message' => 'Device successfully paired',
            'device'  => $device
        ], 200);
    }

}
