<?php

namespace App\Http\Controllers\TipsSuggestions;

use App\Http\Controllers\Controller;
use App\Services\GeminiApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TipsController extends Controller
{
    // protected $geminiService;

    // public function __construct(GeminiApiService $geminiService)
    // {
    //     $this->geminiService = $geminiService;
    // }

    // public function generateTips(Request $request)
    // {
    //     try {
    //         $topic = $request->input('topic', 'general plant care');
    //         $context = $request->input('context', 'hydroponic lettuce');

    //         $tips = $this->geminiService->generateEcoTips($topic, $context);

    //         return response()->json([
    //             'topic' => $topic,
    //             'context' => $context,
    //             'tips' => $tips,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'error' => 'Failed to generate eco tips',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    //     public function generateTips(Request $request)
    //     {
    //         $topic = $request->input('topic', 'general plant care');
    //         $context = $request->input('context', 'hydroponic lettuce system');

    //         $prompt = <<<PROMPT
    // Generate clear, structured eco tips for a hydroponic setup about "{$topic}" in the context of "{$context}".
    // Focus on: water quality, nutrients, plant growth, and maintenance.
    // Format the response as **valid JSON** with fields:
    // - category
    // - title
    // - description
    // - bullet_points
    // PROMPT;

    //         $response = Http::withHeaders([
    //             'Content-Type' => 'application/json',
    //             'x-goog-api-key' => env('GEMINI_API_KEY'),
    //         ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent', [
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

    //         // ✅ Extract the AI-generated text
    //         $data = $response->json();
    //         $output = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

    //         // ✅ Try to decode JSON safely
    //         $decoded = json_decode($output, true);

    //         // If AI didn't return valid JSON, return raw text instead
    //         if (json_last_error() !== JSON_ERROR_NONE) {
    //             return response()->json([
    //                 'topic' => $topic,
    //                 'context' => $context,
    //                 'raw_output' => $output,
    //             ]);
    //         }

    //         // ✅ Return structured JSON tips
    //         return response()->json([
    //             'topic' => $topic,
    //             'context' => $context,
    //             'tips' => $decoded,
    //         ]);
    //     }

    public function generateTips(Request $request)
    {
        $topic = $request->input('topic', 'general plant care');
        $context = $request->input('context', 'hydroponic lettuce system');

        $prompt = <<<PROMPT
Generate clear, structured eco tips for a hydroponic setup about "{$topic}" in the context of "{$context}".
Focus on: water quality, nutrients, plant growth, and maintenance.
Format the response as valid JSON with fields:
- category
- title
- description
- bullet_points (can contain nested heading/tips pairs)
PROMPT;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => env('GEMINI_API_KEY'),
        ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent', [
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

        $data = $response->json();
        $output = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$output) {
            return response()->json(['error' => 'No text generated from Gemini.'], 500);
        }

        // 🧹 Remove markdown code fences (```json ... ```)
        $cleanOutput = preg_replace('/^```(json)?\s*|\s*```$/m', '', trim($output));

        // 🧩 Attempt to decode the cleaned JSON
        $decoded = json_decode($cleanOutput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'topic' => $topic,
                'context' => $context,
                'raw_output' => $output,
                'error' => 'Invalid JSON structure detected'
            ]);
        }

        return response()->json([
            'topic' => $topic,
            'context' => $context,
            'tips' => $decoded,
        ]);
    }
}
