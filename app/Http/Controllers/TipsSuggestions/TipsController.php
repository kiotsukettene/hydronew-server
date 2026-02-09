<?php

namespace App\Http\Controllers\TipsSuggestions;

use App\Http\Controllers\Controller;
use App\Models\SensorReading;
use App\Models\SensorSystem;
use App\Services\GeminiApiService;
use App\Services\RAGInsightsService;
use Illuminate\Http\Request;

class TipsController extends Controller
{
    protected GeminiApiService $geminiService;
    protected RAGInsightsService $ragService;

    public function __construct(GeminiApiService $geminiService, RAGInsightsService $ragService)
    {
        $this->geminiService = $geminiService;
        $this->ragService = $ragService;
    }

    public function generateTips(Request $request)
    {
        $user = $request->user();

        // Optional: Filter by device_id and system_type from request
        $deviceId = $request->input('device_id');
        $systemType = $request->input('system_type', 'hydroponics_water');

        // Build query for the latest sensor reading
        $query = SensorReading::whereHas('sensorSystem', function ($q) use ($deviceId, $systemType) {
            $q->where('is_active', true);
            
            // Filter by device_id if provided
            if ($deviceId) {
                $q->where('device_id', $deviceId);
            }
            
            // Filter by system_type (default: hydroponics_water)
            if ($systemType) {
                $q->where('system_type', $systemType);
            }
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

        // Extract sensor values from the reading
        $ph = $latestReading->ph;
        $tds = $latestReading->tds;
        $turbidity = $latestReading->turbidity;
        $ec = $latestReading->ec;
        $waterLevel = $latestReading->water_level;


        $qualityMsg = $this->evaluateQuality($ph, $tds, $turbidity, $ec);


        $context = [
            'ph' => $ph,
            'tds' => $tds,
            'turbidity' => $turbidity,
            'ec' => $ec,
            'water_level' => $waterLevel,
            'status' => $qualityMsg,
        ];

        $prompt = <<<PROMPT
You are an expert in hydroponics and sustainable water reuse, helping small farmers and home growers.
Your job is to give **simple, friendly, and actionable advice** — not technical or scientific explanations.

Here is the user's real-time data from the water quality sensors before they go in the hydroponic system:

Water Quality Readings:
- pH: {$ph}
- TDS: {$tds} ppm
- Turbidity: {$turbidity} NTU
- EC: {$ec} mS/cm
- Water Level: {$waterLevel}
Overall Status: {$qualityMsg}

Write practical, easy-to-follow tips to help the user improve water quality, nutrient balance, and plant health.
Avoid deep technical terms like "calibration", "mS/cm", or "microbial loading".
Instead, explain things in simple ways, e.g.:
- “Add more nutrients until the water reaches the right level for lettuce.”
- “If the water looks cloudy, clean the filter or replace part of the water.”
- “Make sure your system has enough light and the water keeps moving.”

Be concise, one sentence, and Focus on:
1. What the user should do now to fix or improve the situation.
2. How to keep the kratky hydroponic system healthy long-term.
3. Easy eco-friendly practices for saving water and tips for plant growth.

Keep the tone friendly and encouraging, as if teaching a beginner farmer.

Format the response as **valid JSON** with:
{
  "category": "string",
  "title": "string",
  "description": "string",
  "bullet_points": [
      {
        "heading": "string",
        "tips": ["string", "string", "string"]
      }
  ]
}

No markdown, no extra explanations, make the heading 2-3 words only, and in tips, it should not say plant, it should say lettuce, and nutrient solution and words for hydroponics, and the bullet points should be 3 only — just valid JSON.
PROMPT;


        $result = $this->geminiService->generateContent($prompt);

        if (!$result['success']) {
            $statusCode = $result['status'] ?? 500;
            return response()->json([
                'error' => $result['error'],
                'details' => $result['details'] ?? null,
                'raw_output' => $result['raw_output'] ?? null
            ], $statusCode);
        }

        $decoded = $result['data'];

        // Load sensor system relationship for additional context
        $latestReading->load('sensorSystem');

        // Return combined data
        return response()->json([
            'user' => $user->id,
            'sensor_data' => [
                'ph' => $ph,
                'tds' => $tds,
                'turbidity' => $turbidity,
                'ec' => $ec,
                'water_level' => $waterLevel,
                'reading_time' => $latestReading->reading_time,
                'system_type' => $latestReading->sensorSystem->system_type ?? null,
                'device_id' => $latestReading->sensorSystem->device_id ?? null,
            ],
            'quality' => $qualityMsg,
            'tips' => $decoded,
        ]);
    }


    protected function evaluateQuality($ph, $tds, $turbidity, $ec)
    {
        if (is_null($ph) || is_null($tds) || is_null($turbidity) || is_null($ec)) {
            return 'Unknown';
        }

        $isPhSafe = ($ph >= 6.5 && $ph <= 8.0);
        $isTdsSafe = ($tds >= 560 && $tds <= 840);
        $isTurbiditySafe = ($turbidity <= 5);
        $isEcSafe = ($ec >= 1.2 && $ec <= 2.5);

        if ($isPhSafe && $isTdsSafe && $isTurbiditySafe && $isEcSafe) {
            return 'Safe for plants';
        }

        return 'Unsafe for plants';
    }

    /**
     * Generate RAG-enhanced insights using historical patterns
     */
    public function generateRagInsights(Request $request)
    {
        $user = $request->user();

        // Get device_id and system_type from request
        $deviceId = $request->input('device_id');
        $systemType = $request->input('system_type', 'dirty_water');

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
                'message' => $result['message'] ?? null
            ], 500);
        }

        // Return RAG-enhanced insights with retrieved context
        return response()->json([
            'user' => $user->id,
            'system_type' => $systemType,
            'device_id' => $latestReading->sensorSystem->device_id ?? null,
            'current_reading' => $result['current_reading'],
            'insights' => $result['insights'],
            'retrieved_context' => $result['retrieved_context'],
            'note' => $result['note'] ?? 'Insights generated using historical pattern retrieval (RAG)'
        ]);
    }
}
