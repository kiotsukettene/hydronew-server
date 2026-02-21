<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    /**
     * Generate embedding vector using Gemini Embedding API
     *
     * @param string $text The text to embed
     * @param string $model The embedding model to use (default: text-embedding-004)
     * @return array Returns ['success' => bool, 'embedding' => array, 'model' => string, 'dimensions' => int] or error
     */
    public function generateEmbedding(string $text, string $model = 'gemini-embedding-001', int $outputDimensionality = 768): array
    {
        try {
            /** @var Response $response */
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
            ])->timeout(60)
                ->retry(3, 1000)
                ->post("{$this->baseUrl}/models/{$model}:embedContent", [
                    'content' => [
                        'parts' => [['text' => $text]],
                    ],
                    'taskType' => 'RETRIEVAL_DOCUMENT',
                    'outputDimensionality' => $outputDimensionality,
                ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'error' => 'Gemini Embedding API request failed',
                    'details' => $response->json(),
                    'status' => $response->status()
                ];
            }

            $data = $response->json();
            $embedding = $data['embedding']['values'] ?? null;

            if (!$embedding || !is_array($embedding)) {
                return [
                    'success' => false,
                    'error' => 'No embedding vector returned from Gemini',
                    'response' => $data
                ];
            }

            // Normalize the vector to unit length for efficient cosine similarity
            $normalizedEmbedding = $this->normalizeVector($embedding);
            $dimensions = count($normalizedEmbedding);

            return [
                'success' => true,
                'embedding' => $normalizedEmbedding,
                'model' => $model,
                'dimensions' => $dimensions
            ];

        } catch (\Exception $e) {
            Log::error('Gemini Embedding: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'model' => $model,
                'text_length' => strlen($text)
            ]);

            return [
                'success' => false,
                'error' => 'Exception during embedding generation',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    /**
     * Normalize a vector to unit length (L2 normalization)
     *
     * @param array $vector The vector to normalize
     * @return array The normalized vector
     */
    private function normalizeVector(array $vector): array
    {
        // Calculate the L2 norm (magnitude)
        $magnitude = sqrt(array_sum(array_map(fn($val) => $val * $val, $vector)));

        // Avoid division by zero
        if ($magnitude == 0) {
            return $vector;
        }

        // Normalize each component
        return array_map(fn($val) => $val / $magnitude, $vector);
    }
}
