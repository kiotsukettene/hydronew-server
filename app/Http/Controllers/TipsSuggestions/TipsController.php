<?php

namespace App\Http\Controllers\TipsSuggestions;

use App\Http\Controllers\Controller;
use App\Models\SensorReading;
use App\Services\GeminiApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TipsController extends Controller
{

//     public function generateTips(Request $request)
//     {
//         $topic = $request->input('topic', 'general plant care');
//         $context = $request->input('context', 'hydroponic lettuce system');

//        $prompt = <<<PROMPT
// You are an expert in hydroponics, wastewater treatment, and sustainable agriculture.
// Generate practical, clear, and structured eco-tips for users of a smart IoT-based hydroponic system powered by Microbial Fuel Cells (MFCs) that treat organic greywater for plant growth.

// Context:
// - The system includes MFC-based wastewater treatment, natural filters (sand, anthracite, pebbles, charcoal), and UV filtration.
// - IoT sensors monitor pH, TDS, turbidity, temperature, and water level.
// - The AI model uses Random Forest C to optimize treatment and predict efficiency.
// - The hydroponic system is designed for lettuce cultivation using treated greywater.

// Focus the tips on:
// 1. Water quality management and monitoring
// 2. Nutrient balance and dosing for hydroponic lettuce
// 3. Plant growth optimization and maintenance
// 4. Efficient IoT system operation and troubleshooting
// 5. Eco-friendly practices and energy efficiency in MFC-powered systems

// Format the response as **valid JSON** with the following fields:
// {
//   "category": "string",
//   "title": "string",
//   "description": "string",
//   "bullet_points": [
//       {
//         "heading": "string",
//         "tips": ["string", "string", "string"]
//       }
//   ]
// }

// Ensure the JSON is properly formatted with no markdown or extra text outside the JSON.
// PROMPT;
//         $response = Http::withHeaders([
//             'Content-Type' => 'application/json',
//             'x-goog-api-key' => env('GEMINI_API_KEY'),
//         ])->timeout(90)
//         ->retry(3, 2000) // retry up to 3 times, 2s apart
//         ->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent', [
//             'contents' => [[
//                 'parts' => [['text' => $prompt]],
//             ]],
//         ]);

//         if ($response->failed()) {
//             return response()->json([
//                 'error' => 'Gemini API request failed',
//                 'details' => $response->json(),
//                 'status' => $response->status()
//             ], $response->status());
//         }

//         $data = $response->json();
//         $output = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

//         if (!$output) {
//             return response()->json(['error' => 'No text generated from Gemini.'], 500);
//         }

//         // 🧹 Remove markdown code fences (```json ... ```)
//         $cleanOutput = preg_replace('/^```(json)?\s*|\s*```$/m', '', trim($output));

//         // 🧩 Attempt to decode the cleaned JSON
//         $decoded = json_decode($cleanOutput, true);

//         if (json_last_error() !== JSON_ERROR_NONE) {
//             return response()->json([
//                 'topic' => $topic,
//                 'context' => $context,
//                 'raw_output' => $output,
//                 'error' => 'Invalid JSON structure detected'
//             ]);
//         }

//         return response()->json([
//             'topic' => $topic,
//             'context' => $context,
//             'tips' => $decoded,
//         ]);
//     }


    public function generateTips(Request $request)
    {
        // ✅ Step 1: Fetch latest readings by type
        $requiredTypes = ['ph', 'turbidity', 'tds', 'ec', 'water_level'];

        $latestReadingsByType = SensorReading::selectRaw('MAX(id) as id')
            ->groupBy('sensor_id');

        $readings = SensorReading::whereIn('id', $latestReadingsByType)
            ->with('sensor:id,type,unit')
            ->whereHas('sensor', function ($query) use ($requiredTypes) {
                $query->whereIn('type', $requiredTypes);
            })
            ->get()
            ->keyBy('sensor.type');

        // Extract values
        $ph = $readings->get('ph')?->reading_value;
        $tds = $readings->get('tds')?->reading_value;
        $turbidity = $readings->get('turbidity')?->reading_value;
        $ec = $readings->get('ec')?->reading_value;
        $waterLevel = $readings->get('water_level')?->reading_value;

        // ✅ Step 2: Determine water quality
        $qualityMsg = $this->evaluateQuality($ph, $tds, $turbidity, $ec);

        // ✅ Step 3: Build contextual prompt for Gemini
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

Here is the user's real-time data from a smart hydroponic lettuce system that uses treated greywater and Microbial Fuel Cells (MFCs):

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

        // ✅ Step 4: Send request to Gemini API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => env('GEMINI_API_KEY'),
        ])->timeout(90)
            ->retry(3, 2000)
            ->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent', [
                'contents' => [[
                    'parts' => [['text' => $prompt]],
                ]],
            ]);

        if ($response->failed()) {
            return response()->json([
                'error' => 'Gemini API request failed',
                'details' => $response->json(),
                'status' => $response->status()
            ], $response->status());
        }

        // ✅ Step 5: Clean and decode Gemini’s JSON output
        $data = $response->json();
        $output = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$output) {
            return response()->json(['error' => 'No text generated from Gemini.'], 500);
        }

        $cleanOutput = preg_replace('/^```(json)?\s*|\s*```$/m', '', trim($output));
        $decoded = json_decode($cleanOutput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'context' => $context,
                'raw_output' => $output,
                'error' => 'Invalid JSON structure detected'
            ]);
        }

        // ✅ Step 6: Return combined data
        return response()->json([
            'context' => $context,
            'quality' => $qualityMsg,
            'tips' => $decoded,
        ]);
    }

    // 🧩 Helper: Evaluate water quality
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
}
