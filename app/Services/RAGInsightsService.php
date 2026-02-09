<?php

namespace App\Services;

use App\Models\SensorReading;
use Illuminate\Support\Facades\Log;

class RAGInsightsService
{
    private GeminiApiService $geminiService;
    private VectorStoreService $vectorStore;

    public function __construct(GeminiApiService $geminiService, VectorStoreService $vectorStore)
    {
        $this->geminiService = $geminiService;
        $this->vectorStore = $vectorStore;
    }

    /**
     * Generate AI insights augmented with retrieved historical patterns
     *
     * @param SensorReading $currentReading The current sensor reading
     * @param string $systemType The system type (dirty_water, clean_water, hydroponics_water)
     * @param int|null $deviceId Optional device ID filter
     * @return array Returns insights with retrieved context
     */
    public function generateInsights(
        SensorReading $currentReading,
        string $systemType,
        ?int $deviceId = null
    ): array {
        try {
            // Step 1: Build pattern summary text from current reading
            $currentPatternText = $this->buildPatternText($currentReading, $systemType);

            Log::info('RAG: Building insights', [
                'system_type' => $systemType,
                'device_id' => $deviceId,
                'pattern_text' => $currentPatternText
            ]);

            // Step 2: Generate embedding for current state
            $embeddingResult = $this->geminiService->generateEmbedding($currentPatternText);

            if (!$embeddingResult['success']) {
                Log::error('RAG: Failed to generate embedding', [
                    'error' => $embeddingResult['error'] ?? 'Unknown error'
                ]);
                
                // Fallback to non-RAG insights
                return $this->generateFallbackInsights($currentReading, $systemType);
            }

            // Step 3: Retrieve top 3 similar historical patterns
            $retrievedPatterns = $this->vectorStore->search(
                $embeddingResult['embedding'],
                $systemType,
                3,
                $deviceId
            );

            Log::info('RAG: Retrieved patterns', [
                'count' => count($retrievedPatterns),
                'top_score' => $retrievedPatterns[0]['similarity_score'] ?? 'N/A'
            ]);

            // Step 4: Build augmented prompt with retrieved context
            $augmentedPrompt = $this->buildAugmentedPrompt(
                $currentReading,
                $systemType,
                $retrievedPatterns
            );

            // Step 5: Call Gemini generation API
            $generationResult = $this->geminiService->generateContent($augmentedPrompt);

            if (!$generationResult['success']) {
                Log::error('RAG: Failed to generate insights', [
                    'error' => $generationResult['error'] ?? 'Unknown error'
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Failed to generate insights',
                    'details' => $generationResult
                ];
            }

            // Step 6: Return insights with retrieved context for transparency
            return [
                'success' => true,
                'insights' => $generationResult['data'],
                'retrieved_context' => $retrievedPatterns,
                'current_reading' => [
                    'ph' => $currentReading->ph,
                    'tds' => $currentReading->tds,
                    'turbidity' => $currentReading->turbidity,
                    'ec' => $currentReading->ec,
                    'water_level' => $currentReading->water_level,
                    'ai_classification' => $currentReading->ai_classification,
                    'confidence' => $currentReading->confidence,
                    'reading_time' => $currentReading->reading_time,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('RAG: Exception during insight generation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Exception during insight generation',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Build natural language pattern text from sensor reading
     *
     * @param SensorReading $reading
     * @param string $systemType
     * @return string
     */
    private function buildPatternText(SensorReading $reading, string $systemType): string
    {
        $systemLabel = ucfirst(str_replace('_', ' ', $systemType));
        
        $text = "{$systemLabel}: ";
        $text .= "pH {$reading->ph}, ";
        $text .= "TDS {$reading->tds} ppm, ";
        $text .= "turbidity {$reading->turbidity} NTU, ";
        $text .= "EC {$reading->ec} mS/cm";

        if ($reading->ai_classification) {
            $text .= ", classified as {$reading->ai_classification}";
            if ($reading->confidence) {
                $confidencePct = round($reading->confidence * 100);
                $text .= " with {$confidencePct}% confidence";
            }
        }

        if ($reading->water_level) {
            $text .= ", water level {$reading->water_level}";
        }

        return $text;
    }

    /**
     * Build augmented prompt with current reading and retrieved historical patterns
     *
     * @param SensorReading $currentReading
     * @param string $systemType
     * @param array $retrievedPatterns
     * @return string
     */
    private function buildAugmentedPrompt(
        SensorReading $currentReading,
        string $systemType,
        array $retrievedPatterns
    ): string {
        $systemLabel = ucfirst(str_replace('_', ' ', $systemType));
        
        $prompt = <<<PROMPT
You are an expert in hydroponics and water quality management for sustainable farming.
Your job is to give **simple, friendly, and actionable advice** based on current sensor readings and similar historical patterns.

CURRENT WATER QUALITY READINGS ({$systemLabel}):
- pH: {$currentReading->ph}
- TDS: {$currentReading->tds} ppm
- Turbidity: {$currentReading->turbidity} NTU
- EC: {$currentReading->ec} mS/cm
- Water Level: {$currentReading->water_level}
PROMPT;

        if ($currentReading->ai_classification) {
            $prompt .= "\n- AI Classification: {$currentReading->ai_classification}";
            if ($currentReading->confidence) {
                $confidencePct = round($currentReading->confidence * 100);
                $prompt .= " (confidence: {$confidencePct}%)";
            }
        }

        // Add retrieved historical context
        if (!empty($retrievedPatterns)) {
            $prompt .= "\n\nSIMILAR HISTORICAL PATTERNS (for context):\n";
            
            foreach ($retrievedPatterns as $index => $pattern) {
                $num = $index + 1;
                $period = date('M d', strtotime($pattern['period_start'])) . ' - ' . date('M d', strtotime($pattern['period_end']));
                $metadata = $pattern['metadata'];
                
                $prompt .= "\n{$num}. {$period}:\n";
                $prompt .= "   {$pattern['pattern_text']}\n";
                
                if (isset($metadata['avg_ph'])) {
                    $prompt .= "   Stats: pH {$metadata['avg_ph']}, TDS {$metadata['avg_tds']} ppm, Turbidity {$metadata['avg_turbidity']} NTU\n";
                }
                
                if (isset($metadata['classification_good_pct'])) {
                    $goodPct = round($metadata['classification_good_pct']);
                    $badPct = round($metadata['classification_bad_pct']);
                    $prompt .= "   Classification: {$goodPct}% good, {$badPct}% bad\n";
                }
            }
        }

        $prompt .= <<<PROMPT


Based on the current readings and similar historical patterns above, provide practical advice for the user.

GUIDELINES:
- Keep language simple and friendly (avoid technical jargon)
- Focus on actionable steps the user can take now
- Reference patterns from history when relevant (e.g., "Similar to mid-January when...")
- For dirty water: focus on filtration effectiveness and treatment readiness
- For clean water: focus on plant safety and nutrient balance for lettuce/kratky hydroponics
- Be concise - one sentence per tip

Format the response as **valid JSON** with:
{
  "category": "string",
  "title": "string",
  "description": "string",
  "bullet_points": [
      {
        "heading": "string (2-3 words)",
        "tips": ["string", "string", "string"]
      }
  ]
}

No markdown, no extra explanations - just valid JSON with 3 tips per heading.
PROMPT;

        return $prompt;
    }

    /**
     * Generate fallback insights without RAG (when embedding fails)
     *
     * @param SensorReading $currentReading
     * @param string $systemType
     * @return array
     */
    private function generateFallbackInsights(SensorReading $currentReading, string $systemType): array
    {
        $systemLabel = ucfirst(str_replace('_', ' ', $systemType));
        
        $prompt = <<<PROMPT
You are an expert in hydroponics and water quality management.
Provide simple, actionable advice based on these {$systemLabel} readings:

- pH: {$currentReading->ph}
- TDS: {$currentReading->tds} ppm
- Turbidity: {$currentReading->turbidity} NTU
- EC: {$currentReading->ec} mS/cm
- Water Level: {$currentReading->water_level}

Format as valid JSON:
{
  "category": "string",
  "title": "string",
  "description": "string",
  "bullet_points": [
      {
        "heading": "string (2-3 words)",
        "tips": ["string", "string", "string"]
      }
  ]
}
PROMPT;

        $result = $this->geminiService->generateContent($prompt);

        if ($result['success']) {
            return [
                'success' => true,
                'insights' => $result['data'],
                'retrieved_context' => [],
                'note' => 'Generated without historical context (RAG unavailable)',
                'current_reading' => [
                    'ph' => $currentReading->ph,
                    'tds' => $currentReading->tds,
                    'turbidity' => $currentReading->turbidity,
                    'ec' => $currentReading->ec,
                    'water_level' => $currentReading->water_level,
                ]
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to generate fallback insights',
            'details' => $result
        ];
    }
}

