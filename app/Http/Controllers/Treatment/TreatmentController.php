<?php

namespace App\Http\Controllers\Treatment;

use App\Http\Controllers\Controller;
use App\Models\TreatmentReport;
use App\Models\TreatmentStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TreatmentController extends Controller
{
    /**
     * Get the latest treatment report for the user's device.
     * If the report is pending, includes its treatment_stages so the frontend can show stage statuses.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLatestTreatmentReport(Request $request)
    {
        $user = $request->user();
        $device = $user->devices()->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No device connected to this user'
            ], 404);
        }

        $treatmentReport = TreatmentReport::where('device_id', $device->id)
            ->latest('start_time')
            ->first();

        if (!$treatmentReport) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No treatment report found'
            ], 200);
        }

        $isPending = $treatmentReport->final_status === 'pending';

        if ($isPending) {
            $treatmentReport->load(['treatment_stages' => function ($query) {
                $query->orderBy('stage_order');
            }]);
        }

        $payload = [
            'id' => $treatmentReport->id,
            'device_id' => $treatmentReport->device_id,
            'start_time' => $treatmentReport->start_time?->toIso8601String(),
            'end_time' => $treatmentReport->end_time?->toIso8601String(),
            'final_status' => $treatmentReport->final_status,
            'total_cycles' => $treatmentReport->total_cycles,
        ];

        if ($isPending && $treatmentReport->relationLoaded('treatment_stages')) {
            $payload['stages'] = $treatmentReport->treatment_stages->map(function ($stage) {
                return [
                    'id' => $stage->id,
                    'stage_name' => $stage->stage_name,
                    'stage_order' => $stage->stage_order,
                    'status' => $stage->status,
                    'ph' => $stage->pH,
                    'tds' => $stage->TDS,
                    'turbidity' => $stage->turbidity,
                    'notes' => $stage->notes,
                ];
            })->values();
        }

        return response()->json([
            'success' => true,
            'data' => $payload
        ], 200);
    }

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

    /**
     * Save treatment stage for the current/ongoing treatment report
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveTreatmentStage(Request $request)
    {
        \Log::info('saveTreatmentStage called', [
            'user_id' => $request->user()->id ?? null,
            'request_data' => $request->all()
        ]);

        $user = $request->user();
        $device = $user->devices()->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No device connected to this user'
            ], 404);
        }

        // Find the current/ongoing treatment report (pending status)
        $treatmentReport = TreatmentReport::where('device_id', $device->id)
            ->where('final_status', 'pending')
            ->latest('start_time')
            ->first();

        if (!$treatmentReport) {
            return response()->json([
                'success' => false,
                'message' => 'No active treatment in progress found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'stage_name' => 'required|string|in:MFC,Natural Filter,UV Filter,Clean Water Tank',
            'stage_order' => 'required|integer|min:0',
            'status' => 'required|string|in:pending,processing,passed,failed',
            'ph' => 'nullable|numeric',
            'tds' => 'nullable|numeric',
            'turbidity' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $treatmentStage = TreatmentStage::create([
                'treatment_id' => $treatmentReport->id,
                'stage_name' => $request->stage_name,
                'stage_order' => $request->stage_order,
                'status' => $request->status,
                'pH' => $request->ph,
                'TDS' => $request->tds,
                'turbidity' => $request->turbidity,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Treatment stage saved successfully',
                'data' => $treatmentStage
            ], 201);
        } catch (\Exception $e) {
            \Log::error('saveTreatmentStage: Failed to save treatment stage', [
                'user_id' => $user->id,
                'treatment_id' => $treatmentReport->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save treatment stage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update treatment stage status by stage_order (e.g. MFC=1 → processing → passed/failed)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTreatmentStage(Request $request)
    {
        $user = $request->user();
        $device = $user->devices()->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No device connected to this user'
            ], 404);
        }

        // Find the current/ongoing treatment report (pending status)
        $treatmentReport = TreatmentReport::where('device_id', $device->id)
            ->where('final_status', 'pending')
            ->latest('start_time')
            ->first();

        if (!$treatmentReport) {
            return response()->json([
                'success' => false,
                'message' => 'No active treatment in progress found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'stage_order' => 'required|integer|min:1',
            'status' => 'required|string|in:pending,processing,passed,failed',
            'ph' => 'nullable|numeric',
            'tds' => 'nullable|numeric',
            'turbidity' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $treatmentStage = TreatmentStage::where('treatment_id', $treatmentReport->id)
            ->where('stage_order', $request->stage_order)
            ->first();

        if (!$treatmentStage) {
            return response()->json([
                'success' => false,
                'message' => 'Treatment stage not found for stage order ' . $request->stage_order
            ], 404);
        }

        try {
            $updateData = ['status' => $request->status];
            if ($request->has('ph')) {
                $updateData['pH'] = $request->ph;
            }
            if ($request->has('tds')) {
                $updateData['TDS'] = $request->tds;
            }
            if ($request->has('turbidity')) {
                $updateData['turbidity'] = $request->turbidity;
            }
            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }

            $treatmentStage->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Treatment stage updated successfully',
                'data' => $treatmentStage->fresh()
            ], 200);
        } catch (\Exception $e) {
            \Log::error('updateTreatmentStage: Failed to update treatment stage', [
                'user_id' => $user->id,
                'stage_order' => $request->stage_order,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update treatment stage',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
