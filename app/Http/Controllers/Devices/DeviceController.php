<?php

namespace App\Http\Controllers\Devices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PairingToken;
use Illuminate\Support\Str;
use App\Models\Device;
use App\Models\DeviceUser;
use App\Console\Commands\MqttListen;
use App\Services\MqttService;

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
    \Log::info('Provision method called', [
        'headers' => $request->headers->all(),
        'body' => $request->all()
    ]);

    $validated = $request->validate([
        'pairing_token'    => 'required|string',
        'serial_number'    => 'required|string',
        'machine_name'     => 'nullable|string',
        'model'            => 'nullable|string',
        'firmware_version' => 'nullable|string',
    ]);

    $tokenHash = hash('sha256', $validated['pairing_token']);

    $pairingToken = PairingToken::where('token_hash', $tokenHash)
        ->whereNull('used_at')
        ->where('expires_at', '>', now())
        ->first();

    if (!$pairingToken) {
        return response()->json(['message' => 'Invalid or expired pairing token'], 401);
    }

    $device = Device::where('serial_number', $validated['serial_number'])->first();
    if (!$device) {
        return response()->json(['message' => 'Device not found in database'], 404);
    }

    $existingLink = DeviceUser::where('device_id', $device->id)
        ->where('user_id', $pairingToken->user_id)
        ->first();

    if ($existingLink) {
            app(MqttService::class)->publish("devices/{$pairingToken->user_id}/pairing", "User already paired", 1, true);
        return response()->json([
            'message' => 'You already paired with this device'
        ], 409);
    }

    DeviceUser::create([
        'device_id' => $device->id,
        'user_id'   => $pairingToken->user_id
    ]);

    $pairingToken->update(['used_at' => now()]);

    $device->update([
        'device_name'      => $validated['machine_name'] ?? $device->device_name,
        'model'            => $validated['model'] ?? $device->model,
        'firmware_version' => $validated['firmware_version'] ?? $device->firmware_version,
        'status'           => 'online'
    ]);


    $payload = [
        'device' => [
            'id' => $device->id,
            'device_name' => $device->device_name,
            'serial_number' => $device->serial_number,
            'model' => $device->model,
            'firmware_version' => $device->firmware_version,
            'status' => $device->status,
        ]
    ];


    app(MqttService::class)->publish("devices/{$pairingToken->user_id}/pairing", $payload, 1, false);

    return response()->json([
        'message' => 'Device successfully paired',
        'device' => $payload['device']
    ], 200);
}


    public function fetchDevices(Request $request)
    {
        $user = $request->user();

        $devices = $user->devices()->get([
            'devices.id',
            'devices.device_name',
            'devices.serial_number',
            'devices.model',
            'devices.firmware_version',
            'devices.status',
            'devices.updated_at',
        ]);

        return response()->json([
            'status' => 'success',
            'devices' => $devices,
        ], 200);
    }

    /**
     * Unpair the current user from their device(s) by removing the user from device_users.
     * Uses the logged-in user's id; no device_id needed.
     */
    public function unpair(Request $request)
    {
        $user = $request->user();

        $deleted = DeviceUser::where('user_id', $user->id)->delete();

        if ($deleted === 0) {
            return response()->json([
                'message' => 'No device paired with your account.',
            ], 404);
        }

        return response()->json([
            'message' => 'Device unpaired successfully.',
        ], 200);
    }

    /**
     * Get QR payload for the logged-in user's primary device.
     *
     * The frontend will take this JSON and generate a QR code from it.
     * Suggested QR content:
     * {
     *   "serial_number": "...",
     *   "device_name": "...",
     *   "model": "..."
     * }
     */
    public function generateQrPayload(Request $request)
    {
        $user = $request->user();

        // Assuming a user can have multiple devices; pick the first for now.
        // Adjust selection logic as needed (e.g. by passing a device_id).
        $device = $user->devices()->first();

        if (!$device) {
            return response()->json([
                'message' => 'No device connected to this user.',
            ], 404);
        }

        $payload = [
            'serial_number' => $device->serial_number,
            'device_name'   => $device->device_name,
            'model'         => $device->model,
        ];

        return response()->json([
            'message' => 'QR payload generated successfully.',
            'qr_payload' => $payload,
        ], 200);
    }

    /**
     * Pair the logged-in user to a device using data from a scanned QR code.
     *
     * Expected request payload (from frontend after scanning QR):
     * {
     *   "serial_number": "ABC123456"
     * }
     *
     * The QR itself can contain a richer JSON with device_name/model,
     * but for pairing we only strictly require serial_number.
     */
    public function pairByQr(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'serial_number' => 'required|string',
        ]);

        // Find device by serial_number embedded in the QR
        $device = Device::where('serial_number', $validated['serial_number'])->first();

        if (!$device) {
            return response()->json([
                'message' => 'Device not found for given QR code.',
            ], 404);
        }

        // Optional: ensure that this device is already owned/linked by at least one user
        $hasOwner = DeviceUser::where('device_id', $device->id)->exists();
        if (!$hasOwner) {
            return response()->json([
                'message' => 'Device is not linked to any owner yet.',
            ], 400);
        }

        // Check if this logged-in user is already linked to the device
        $existingLink = DeviceUser::where('device_id', $device->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLink) {
            return response()->json([
                'message' => 'You are already paired with this device.',
            ], 409);
        }

        // Create new device-user link
        DeviceUser::create([
            'device_id' => $device->id,
            'user_id'   => $user->id,
        ]);

        return response()->json([
            'message' => 'Paired to device successfully via QR.',
            'device'  => [
                'id'              => $device->id,
                'device_name'     => $device->device_name,
                'serial_number'   => $device->serial_number,
                'model'           => $device->model,
                'firmware_version'=> $device->firmware_version,
                'status'          => $device->status,
            ],
        ], 200);
    }
}
