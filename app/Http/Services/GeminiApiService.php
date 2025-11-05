<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiApiService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    }

    public function generateEcoTips($topic = 'general plant care', $context = 'hydroponic system')
    {
        $prompt = "Generate clear, structured eco tips for a hydroponic setup about {$topic}.
        Focus on: water quality, nutrients, plant growth, and maintenance.
        Format as JSON with fields: category, title, description, and bullet_points.";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($this->baseUrl, [
            'contents' => [[
                'parts' => [['text' => $prompt]],
            ]],
        ]);

        if ($response->failed()) {
            throw new \Exception('Gemini API request failed: ' . $response->body());
        }

        $data = $response->json();
        $output = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        // Try to decode the JSON AI output safely
        return json_decode($output, true) ?? ['raw_output' => $output];
    }
}
