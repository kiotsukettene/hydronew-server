<?php

namespace App\Http\Controllers\TipsSuggestions;

use App\Http\Controllers\Controller;
use App\Models\SensorReading;
use App\Services\RAGInsightsService;
use Illuminate\Http\Request;

class TipsController extends Controller
{
    protected RAGInsightsService $ragService;

    public function __construct(RAGInsightsService $ragService)
    {
        $this->ragService = $ragService;
    }

    /**
     * Generate RAG-enhanced insights (tips and suggestions) using historical patterns
     */
    public function generateRagInsights(Request $request)
    {
        // Get device_id and system_type from request
        $deviceId = $request->input('device_id');
        $systemType = $request->input('system_type', 'clean_water');

        // Validate system_type
        $validTypes = ['dirty_water', 'clean_water', 'hydroponics_water'];
        if (!in_array($systemType, $validTypes)) {
            return response()->json([
                'error' => 'Invalid system_type',
                'message' => 'system_type must be one of: ' . implode(', ', $validTypes)
            ], 400);
        }

        // Build query for the latest sensor reading
        $query = SensorReading::whereHas('sensorSystem', function ($q) use ($deviceId, $systemType) {
            $q->where('is_active', true);
            
            if ($deviceId) {
                $q->where('device_id', $deviceId);
            }
            
            $q->where('system_type', $systemType);
        });

        // Get the latest reading
        $latestReading = $query->orderBy('reading_time', 'desc')->first();

        // If no reading found, return error
        if (!$latestReading) {
            return response()->json([
                'error' => 'No sensor readings found',
                'message' => 'Please ensure your sensors are connected and transmitting data.',
                'filters' => [
                    'device_id' => $deviceId,
                    'system_type' => $systemType
                ]
            ], 404);
        }

        // Load sensor system relationship
        $latestReading->load('sensorSystem');

        // Generate RAG-enhanced insights
        $result = $this->ragService->generateInsights(
            $latestReading,
            $systemType,
            $deviceId
        );

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error'] ?? 'Failed to generate insights',
                'details' => $result['details'] ?? null,
                'message' => $result['message'] ?? null,
                'validation_errors' => $result['validation_errors'] ?? null,
                'raw_output' => $result['raw_output'] ?? null
            ], 500);
        }

        // Return RAG-enhanced insights with retrieved context
        return response()->json([
            'system_type' => $systemType,
            'device_id' => $latestReading->sensorSystem->device_id ?? null,
            'current_reading' => $result['current_reading'],
            'insights' => $result['insights'],
            'statuses' => $result['statuses'] ?? null,
            'missing_sensors' => $result['missing_sensors'] ?? [],
            'evidence' => $result['evidence'] ?? [],
            'retrieved_context' => $result['retrieved_context'],
            'note' => $result['note'] ?? 'Insights generated using historical pattern retrieval (RAG)'
        ]);
    }
}
