<?php

namespace App\Http\Controllers\TipsSuggestions;

use App\Http\Controllers\Controller;
use App\Models\SensorReading;
use App\Models\TipsSuggestion;
use App\Services\RAGInsightsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TipsController extends Controller
{
    protected RAGInsightsService $ragService;

    public function __construct(RAGInsightsService $ragService)
    {
        $this->ragService = $ragService;
    }

    /**
     * Generate RAG-enhanced insights (tips and suggestions) using historical patterns
     * With 24-hour caching to reduce API calls
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

        // Check for cached tips that haven't expired (24 hours)
        $cached = TipsSuggestion::getCached($deviceId, $systemType);
        
        if ($cached) {
            Log::info('RAG Insights: Returning cached tips', [
                'device_id' => $deviceId,
                'system_type' => $systemType,
                'cached_at' => $cached->created_at,
                'expires_at' => $cached->expires_at
            ]);

            return response()->json([
                'system_type' => $cached->system_type,
                'device_id' => $cached->device_id,
                'current_reading' => $cached->current_reading,
                'insights' => $cached->insights,
                'statuses' => $cached->statuses,
                'missing_sensors' => $cached->missing_sensors ?? [],
                'evidence' => $cached->evidence ?? [],
                'retrieved_context' => $cached->retrieved_context,
                'cached' => true,
                'cached_at' => $cached->created_at,
                'expires_at' => $cached->expires_at,
                'note' => 'Cached insights (valid for 24 hours)'
            ]);
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

        // Save insights to database with 24-hour expiration
        try {
            TipsSuggestion::create([
                'device_id' => $latestReading->sensorSystem->device_id ?? $deviceId,
                'system_type' => $systemType,
                'title' => $result['insights']['title'] ?? 'Water Quality Tips',
                'description' => $result['insights']['description'] ?? '',
                'category' => $result['insights']['category'] ?? 'Water Quality',
                'insights' => $result['insights'],
                'current_reading' => $result['current_reading'],
                'statuses' => $result['statuses'] ?? null,
                'missing_sensors' => $result['missing_sensors'] ?? [],
                'evidence' => $result['evidence'] ?? [],
                'retrieved_context' => $result['retrieved_context'],
                'expires_at' => now()->addDay() // 24 hours from now
            ]);

            Log::info('RAG Insights: Saved to database with 24h cache', [
                'device_id' => $latestReading->sensorSystem->device_id ?? $deviceId,
                'system_type' => $systemType
            ]);
        } catch (\Exception $e) {
            Log::error('RAG Insights: Failed to save to database', [
                'error' => $e->getMessage(),
                'device_id' => $deviceId,
                'system_type' => $systemType
            ]);
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
            'cached' => false,
            'note' => 'Insights generated using historical pattern retrieval (RAG)'
        ]);
    }
}
