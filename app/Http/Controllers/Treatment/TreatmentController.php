<?php

namespace App\Http\Controllers\Treatment;

use App\Http\Controllers\Controller;
use App\Models\TreatmentReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TreatmentController extends Controller
{
    /**
     * Save a new treatment report
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveTreatment(Request $request)
    {
        \Log::info('saveTreatment called', [
            'user_id' => $request->user()->id ?? null,
            'request_data' => $request->all()
        ]);

        // Get the authenticated user
        $user = $request->user();

        // Get the user's connected device (assuming the user has one device)
        $device = $user->devices()->first();

        if (!$device) {
            \Log::warning('saveTreatment: No device found', [
                'user_id' => $user->id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No device connected to this user'
            ], 404);
        }

        \Log::info('saveTreatment: Device found', [
            'user_id' => $user->id,
            'device_id' => $device->id
        ]);

        try {
            // Create the treatment report with automatic values
            $treatmentReport = TreatmentReport::create([
                'device_id' => $device->id,
                'start_time' => now(), // Automatically set to current time
                'end_time' => null, // Set to null when treatment starts
                'final_status' => 'pending', // Default status
                'total_cycles' => null, // Set to null when treatment starts
            ]);

            \Log::info('saveTreatment: Treatment report created successfully', [
                'treatment_id' => $treatmentReport->id,
                'device_id' => $device->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Treatment report saved successfully',
                'data' => $treatmentReport
            ], 201);

        } catch (\Exception $e) {
            \Log::error('saveTreatment: Failed to create treatment report', [
                'user_id' => $user->id,
                'device_id' => $device->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save treatment report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update treatment report with final status and cycles
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTreatment(Request $request)
    {
        \Log::info('updateTreatment called', [
            'user_id' => $request->user()->id ?? null,
            'request_data' => $request->all()
        ]);

        // Get the authenticated user
        $user = $request->user();

        // Get the user's connected device
        $device = $user->devices()->first();

        if (!$device) {
            \Log::warning('updateTreatment: No device found', [
                'user_id' => $user->id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No device connected to this user'
            ], 404);
        }

        \Log::info('updateTreatment: Device found', [
            'user_id' => $user->id,
            'device_id' => $device->id
        ]);

        // Find the treatment report that is currently "pending"
        $treatmentReport = TreatmentReport::where('device_id', $device->id)
            ->where('final_status', 'pending')
            ->latest('start_time')
            ->first();

        if (!$treatmentReport) {
            \Log::warning('updateTreatment: No active treatment found', [
                'user_id' => $user->id,
                'device_id' => $device->id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No active treatment in progress found'
            ], 404);
        }

        \Log::info('updateTreatment: Active treatment found', [
            'treatment_id' => $treatmentReport->id,
            'device_id' => $device->id,
            'user_id' => $user->id,
            'start_time' => $treatmentReport->start_time
        ]);

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'total_cycles' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            \Log::warning('updateTreatment: Validation failed', [
                'user_id' => $user->id,
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update the treatment report from "pending" to "success"
            $treatmentReport->update([
                'final_status' => 'success',
                'end_time' => now(), // Set to current time when API is called
                'total_cycles' => $request->total_cycles,
            ]);

            \Log::info('updateTreatment: Treatment report updated successfully', [
                'treatment_id' => $treatmentReport->id,
                'device_id' => $device->id,
                'user_id' => $user->id,
                'total_cycles' => $request->total_cycles,
                'end_time' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Treatment report updated successfully',
                'data' => $treatmentReport->fresh()
            ], 200);

        } catch (\Exception $e) {
            \Log::error('updateTreatment: Failed to update treatment report', [
                'user_id' => $user->id,
                'device_id' => $device->id ?? null,
                'treatment_id' => $treatmentReport->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update treatment report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
