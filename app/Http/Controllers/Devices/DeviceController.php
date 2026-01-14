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


    app(MqttService::class)->publish("devices/{$pairingToken->user_id}/pairing", $payload, 1, true);

    return response()->json([
        'message' => 'Device successfully paired',
        'device' => $payload['device']
    ], 200);
}





}
