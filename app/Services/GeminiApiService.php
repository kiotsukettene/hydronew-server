<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;

class GeminiApiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private string $model = 'gemini-2.5-flash';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Generate content using Gemini API
     *
     * @param string $prompt The prompt text to send to Gemini
     * @return array Returns the parsed response with 'success' flag and 'data' or 'error'
     */
    public function generateContent(string $prompt): array
    {
        /** @var Response $response */
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $this->apiKey,
        ])->timeout(90)
            ->retry(3, 2000)
            ->post("{$this->baseUrl}/models/{$this->model}:generateContent", [
                'contents' => [[
                    'parts' => [['text' => $prompt]],
                ]],
            ]);

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'Gemini API request failed',
                'details' => $response->json(),
                'status' => $response->status()
            ];
        }

        $data = $response->json();
        $output = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$output) {
            return [
                'success' => false,
                'error' => 'No text generated from Gemini.'
            ];
        }

        // Clean markdown code blocks if present
        $cleanOutput = preg_replace('/^```(json)?\s*|\s*```$/m', '', trim($output));
        $decoded = json_decode($cleanOutput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON structure detected',
                'raw_output' => $output
            ];
        }

        return [
            'success' => true,
            'data' => $decoded,
            'raw_output' => $output
        ];
    }
}
