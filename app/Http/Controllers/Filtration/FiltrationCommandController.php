<?php

namespace App\Http\Controllers\Filtration;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\FiltrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiltrationCommandController extends Controller
{
    public function __construct(
        protected FiltrationService $filtrationService
    ) {
    }

    /**
     * Resolve the device for the authenticated user.
     * If request has 'serial', use that device (must belong to user). Otherwise use user's first device.
     */
    protected function resolveDevice(Request $request): ?Device
    {
        $user = $request->user();
        $serial = $request->input('serial');

        if ($serial) {
            return $user->devices()->where('serial_number', $serial)->first();
        }

        return $user->devices()->first();
    }

    /**
     * Start Process – publish OPEN to mfc/{serial}/pump/3.
     * Backend will publish state when ack=1 is received.
     */
    public function startProcess(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No device found. Pair a device first or provide a valid serial.',
            ], 404);
        }

        $this->filtrationService->publishStartProcessCommand($device->serial_number);

        return response()->json([
            'success' => true,
            'message' => 'Start process command sent. State will update when device acknowledges.',
        ], 200);
    }

    /**
     * Open Valve 1 – publish OPEN to mfc/{serial}/valve/1.
     */
    public function openValve1(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No device found. Pair a device first or provide a valid serial.',
            ], 404);
        }

        $this->filtrationService->publishOpenValve1Command($device->serial_number);

        return response()->json([
            'success' => true,
            'message' => 'Open valve 1 command sent. State will update when device acknowledges.',
        ], 200);
    }

    /**
     * Close Valve 1 – publish CLOSE to mfc/{serial}/valve/1.
     */
    public function closeValve1(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No device found. Pair a device first or provide a valid serial.',
            ], 404);
        }

        $this->filtrationService->publishCloseValve1Command($device->serial_number);

        return response()->json([
            'success' => true,
            'message' => 'Close valve 1 command sent. State will update when device acknowledges.',
        ], 200);
    }

    /**
     * Open Drain Valve – publish OPEN to mfc_fallback/{serial}/valve/2.
     */
    public function openDrainValve(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No device found. Pair a device first or provide a valid serial.',
            ], 404);
        }

        $this->filtrationService->publishOpenDrainValveCommand($device->serial_number);

        return response()->json([
            'success' => true,
            'message' => 'Open drain valve command sent. State will update when device acknowledges.',
        ], 200);
    }

    /**
     * Close Drain Valve – publish CLOSE to mfc_fallback/{serial}/valve/2.
     */
    public function closeDrainValve(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No device found. Pair a device first or provide a valid serial.',
            ], 404);
        }

        $this->filtrationService->publishCloseDrainValveCommand($device->serial_number);

        return response()->json([
            'success' => true,
            'message' => 'Close drain valve command sent. State will update when device acknowledges.',
        ], 200);
    }

    /**
     * Restart – publish OPEN to reservoir_fallback/{serial}/pump/1.
     * Shown after restart notification; backend publishes stage states when ack=1 is received.
     */
    public function restart(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No device found. Pair a device first or provide a valid serial.',
            ], 404);
        }

        $this->filtrationService->publishRestartCommand($device->serial_number);

        return response()->json([
            'success' => true,
            'message' => 'Restart command sent. Stage states will update when device acknowledges.',
        ], 200);
    }

    /**
     * Open Pump 4 – publish OPEN to mfc/{serial}/pump/4.
     */
    public function openPump4(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No device found. Pair a device first or provide a valid serial.',
            ], 404);
        }

        $this->filtrationService->publishOpenPump4Command($device->serial_number);

        return response()->json([
            'success' => true,
            'message' => 'Open pump 4 command sent.',
        ], 200);
    }

    /**
     * Start Pump 2 with target liters for auto-stop.
     * Explicitly starts the pump (always sends OPEN command).
     * 
     * Request body (required):
     * {
     *   "target_liters": 5  // Required: water liters needed for hydroponic setup
     * }
     */
    public function startPump2(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No device found. Pair a device first or provide a valid serial.',
            ], 404);
        }

        // Validate target_liters (required for start)
        $validated = $request->validate([
            'target_liters' => 'required|numeric|min:0.1|max:1000',
        ]);

        $targetLiters = $validated['target_liters'];

        $this->filtrationService->publishStartPump2Command($device->serial_number, $targetLiters);

        $estimatedMinutes = round($targetLiters / 6, 2);

        return response()->json([
            'success' => true,
            'message' => "Pump 2 started. Auto-stop scheduled after {$targetLiters} liters (~{$estimatedMinutes} minutes).",
            'target_liters' => $targetLiters,
            'estimated_minutes' => $estimatedMinutes,
        ], 200);
    }

    /**
     * Stop Pump 2 manually.
     * Checks if pump is running, then sends CLOSE command.
     * If auto-stop job is scheduled, it will check pump state and skip if already stopped.
     * No request body needed.
     */
    public function stopPump2(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No device found. Pair a device first or provide a valid serial.',
            ], 404);
        }

        $wasStopped = $this->filtrationService->publishStopPump2Command($device->serial_number);

        if (!$wasStopped) {
            return response()->json([
                'success' => true,
                'message' => 'Pump 2 is already stopped.',
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pump 2 stop command sent. State will update when device acknowledges.',
        ], 200);
    }
}
