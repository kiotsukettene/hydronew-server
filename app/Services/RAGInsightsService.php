<?php

namespace App\Services;

use App\Models\SensorReading;
use Illuminate\Support\Facades\Log;

class RAGInsightsService
{
    private GeminiApiService $geminiService;
    private VectorStoreService $vectorStore;
    private const MAX_GENERATION_ATTEMPTS = 4;

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
            $missingSensors = $this->getMissingSensors($currentReading, $systemType);
            $warnings = $this->buildWarnings($currentReading, $systemType, $missingSensors);
            $statuses = $this->computeStatuses($currentReading, $systemType, $missingSensors);

            // Step 1: Build pattern summary text from current reading
            $currentPatternText = $this->buildPatternText($currentReading, $systemType);

            Log::info('RAG: Building insights', [
                'system_type' => $systemType,
                'device_id' => $deviceId,
                'pattern_text' => $currentPatternText,
                'missing_sensors' => $missingSensors,
                'statuses' => $statuses,
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
                $retrievedPatterns,
                $missingSensors,
                $statuses,
                $warnings
            );

            // Step 5: Call Gemini generation API with strict validation + retries
            $attempt = 0;
            $generationResult = null;
            $validationErrors = [];

            $lastRawOutput = null;
            while ($attempt < self::MAX_GENERATION_ATTEMPTS) {
                $attempt++;
                $generationResult = $this->geminiService->generateContent($augmentedPrompt);

                if (!$generationResult['success']) {
                    $validationErrors = ['generation_failed: ' . ($generationResult['error'] ?? 'unknown')];
                    $lastRawOutput = $generationResult['raw_output'] ?? null;
                    // If generation completely failed, don't retry
                    if (empty($generationResult['raw_output'])) {
                        break;
                    }
                    continue;
                }

                $lastRawOutput = $generationResult['raw_output'] ?? null;
                $candidate = $generationResult['data'] ?? null;
                $validationErrors = $this->validateInsightsJson($candidate, $missingSensors);
                if (empty($validationErrors)) {
                    break;
                }

                // Tighten prompt on retry with concrete errors
                $augmentedPrompt .= "\n\nRETRY_NOTE: Fix these validation errors and output ONLY valid JSON: "
                    . implode('; ', $validationErrors) . "\n";
            }

            if (!$generationResult || !$generationResult['success'] || !empty($validationErrors)) {
                Log::error('RAG: Failed to produce valid insights after retries', [
                    'attempts' => $attempt,
                    'validation_errors' => $validationErrors,
                    'generation_result' => $generationResult,
                    'raw_output' => $lastRawOutput,
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to generate valid insights',
                    'validation_errors' => $validationErrors,
                    'attempts' => $attempt,
                    'raw_output' => $lastRawOutput,
                    'generation_error' => $generationResult['error'] ?? null,
                ];
            }

            // Step 6: Return insights with retrieved context for transparency
            return [
                'success' => true,
                'insights' => $generationResult['data'],
                'retrieved_context' => $retrievedPatterns,
                'evidence' => $generationResult['data']['evidence'] ?? [],
                'warnings' => $warnings,
                'statuses' => $statuses,
                'missing_sensors' => $missingSensors,
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
        $text .= "pH " . ($reading->ph ?? 'unavailable') . ", ";
        $text .= "TDS " . ($reading->tds ?? 'unavailable') . " ppm";

        // Include turbidity only if NOT hydroponics_water
        if ($systemType !== 'hydroponics_water') {
            $text .= ", turbidity " . ($reading->turbidity ?? 'unavailable') . " NTU";
        }

        // Include EC only if hydroponics_water (skip for clean/dirty water since they have TDS)
        if ($systemType === 'hydroponics_water' && !is_null($reading->ec)) {
            $text .= ", EC {$reading->ec}";
        }

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
        array $retrievedPatterns,
        array $missingSensors,
        array $statuses,
        array $warnings
    ): string {
        $systemLabel = ucfirst(str_replace('_', ' ', $systemType));

        $missingLine = empty($missingSensors) ? 'None' : implode(', ', $missingSensors);
        $noMention = [];
        if (in_array('ec', $missingSensors, true)) {
            $noMention[] = 'EC';
        }
        if (in_array('tds', $missingSensors, true)) {
            $noMention[] = 'TDS';
        }
        $noMentionLine = empty($noMention) ? 'None' : implode(', ', $noMention);
        
        $prompt = <<<PROMPT
You are an expert in hydroponics and water quality management for sustainable farming.
Your job is to give **simple, friendly, and actionable advice** based on current sensor readings and similar historical patterns.

CRITICAL OUTPUT RULES (must follow exactly):
- Output ONLY valid JSON (no markdown).
- Do NOT use the word "plant" or "plants" anywhere; say "lettuce" instead.
- Do NOT mention "mS/cm" or "calibration".
- Do NOT mention "EC" if EC is missing.
- Each tip must be exactly one sentence.
- Exactly 3 bullet_points objects.
- Each bullet_points.tips must be an array of exactly 3 strings.
- Headings must be 2-3 words only.

CURRENT WATER QUALITY READINGS ({$systemLabel}):
- pH: {$currentReading->ph}
- TDS: {$currentReading->tds} ppm
PROMPT;

        // Add turbidity only if NOT hydroponics_water
        if ($systemType !== 'hydroponics_water') {
            $prompt .= "\n- Turbidity: {$currentReading->turbidity} NTU";
        }

        // Add EC only if hydroponics_water
        if ($systemType === 'hydroponics_water' && !in_array('ec', $missingSensors, true)) {
            $prompt .= "\n- EC: {$currentReading->ec}";
        }

        $prompt .= "\n- Water Level: {$currentReading->water_level}\n";

        $prompt .= <<<PROMPT

AUTHORITATIVE STATUS (do not contradict these):
- cleanliness_status: {$statuses['cleanliness_status']}
- nutrient_solution_status: {$statuses['nutrient_solution_status']}

WARNINGS (copy into JSON warnings exactly, or [] if none):
{$this->formatWarningsForPrompt($warnings)}

MISSING SENSORS: {$missingLine}
DO NOT MENTION: {$noMentionLine}
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
                
                $prompt .= "\n{$num}. Context ID {$pattern['id']} ({$period}):\n";
                $prompt .= "   {$pattern['pattern_text']}\n";
                
                if (isset($metadata['avg_ph'])) {
                    $prompt .= "   Stats: pH {$metadata['avg_ph']}, TDS {$metadata['avg_tds']} ppm, Turbidity {$metadata['avg_turbidity']} NTU\n";
                }
                
                if (isset($metadata['classification_good_pct'])) {
                    $goodPct = round($metadata['classification_good_pct']);
                    $badPct = round($metadata['classification_bad_pct']);
                    $prompt .= "   Classification: {$goodPct}% good, {$badPct}% bad\n";
                }

                if (!empty($metadata['missing_fields'])) {
                    $prompt .= "   Missing fields: " . implode(', ', $metadata['missing_fields']) . "\n";
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
  "warnings": ["string"],
  "evidence": [{"context_id": 123, "used_for": "string"}],
  "bullet_points": [
      {
        "heading": "string (2-3 words)",
        "tips": ["string", "string", "string"]
      }
  ]
}

IMPORTANT:
- warnings must exactly match the WARNINGS above (or [] if none)
- evidence.context_id must come from the Context ID numbers shown above
- bullet_points must have exactly 3 objects
- tips arrays must have exactly 3 strings each

No markdown, no extra explanations - just valid JSON.
PROMPT;

        return $prompt;
    }

    private function formatWarningsForPrompt(array $warnings): string
    {
        if (empty($warnings)) {
            return "- None";
        }
        return "- " . implode("\n- ", $warnings);
    }

    private function getMissingSensors(SensorReading $reading, string $systemType = ''): array
    {
        $missing = [];
        $requiredFields = ['ph', 'tds', 'turbidity', 'ec', 'water_level'];
        
        foreach ($requiredFields as $field) {
            // Skip turbidity for hydroponics_water
            if ($field === 'turbidity' && $systemType === 'hydroponics_water') {
                continue;
            }
            
            // Skip EC for clean_water and dirty_water (they have TDS already)
            if ($field === 'ec' && in_array($systemType, ['clean_water', 'dirty_water'])) {
                continue;
            }
            
            if (is_null($reading->{$field})) {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    private function buildWarnings(SensorReading $reading, string $systemType, array $missingSensors): array
    {
        $warnings = [];
        if (in_array('ec', $missingSensors, true)) {
            $warnings[] = 'EC missing — nutrient strength cannot be verified.';
        }
        if (in_array('tds', $missingSensors, true)) {
            $warnings[] = 'TDS missing — nutrient strength cannot be verified.';
        }
        if ($systemType === 'hydroponics_water' && !is_null($reading->tds) && $reading->tds < 100) {
            $warnings[] = 'TDS is very low — nutrient solution may be too weak for lettuce.';
        }
        return $warnings;
    }

    private function computeStatuses(SensorReading $reading, string $systemType, array $missingSensors): array
    {
        $cleanliness = 'unknown';
        
        // For hydroponics_water, turbidity is not required, so skip cleanliness check
        if ($systemType === 'hydroponics_water') {
            $cleanliness = 'not_applicable';
        } elseif (!in_array('turbidity', $missingSensors, true) && !is_null($reading->turbidity)) {
            $cleanliness = ($reading->turbidity <= 5) ? 'clear' : 'cloudy';
        }

        $nutrient = 'unknown';
        if ($systemType === 'hydroponics_water') {
            if (in_array('tds', $missingSensors, true)) {
                $nutrient = 'cannot_verify';
            } else {
                $nutrient = ($reading->tds >= 560 && $reading->tds <= 840) ? 'ready' : 'not_ready';
            }
        } else {
            $nutrient = 'not_applicable';
        }

        return [
            'cleanliness_status' => $cleanliness,
            'nutrient_solution_status' => $nutrient,
        ];
    }

    private function validateInsightsJson($json, array $missingSensors): array
    {
        $errors = [];
        if (!is_array($json)) {
            return ['not_json_object'];
        }

        foreach (['category', 'title', 'description', 'bullet_points', 'warnings', 'evidence'] as $key) {
            if (!array_key_exists($key, $json)) {
                $errors[] = "missing_key:{$key}";
            }
        }

        if (isset($json['bullet_points']) && (!is_array($json['bullet_points']) || count($json['bullet_points']) !== 3)) {
            $errors[] = 'bullet_points_must_be_3';
        }

        if (isset($json['bullet_points']) && is_array($json['bullet_points'])) {
            foreach ($json['bullet_points'] as $i => $bp) {
                if (!is_array($bp) || !isset($bp['heading'], $bp['tips'])) {
                    $errors[] = "bullet_points_invalid_at:{$i}";
                    continue;
                }
                if (!is_array($bp['tips']) || count($bp['tips']) !== 3) {
                    $errors[] = "tips_must_be_3_at:{$i}";
                }
                foreach (($bp['tips'] ?? []) as $j => $tip) {
                    if (!is_string($tip)) {
                        $errors[] = "tip_not_string_at:{$i}:{$j}";
                        continue;
                    }
                    // Check for multiple sentences: replace decimal numbers first, then count sentence endings
                    $tipWithoutDecimals = preg_replace('/\d+\.\d+/', 'NUM', $tip);
                    // Count sentence-ending punctuation (period/question/exclamation followed by space+capital or at end)
                    $sentenceEndings = preg_match_all('/[.!?](?:\s+[A-Z]|$)/', $tipWithoutDecimals);
                    if ($sentenceEndings > 1) {
                        $errors[] = "tip_not_one_sentence_at:{$i}:{$j}";
                    }
                }
            }
        }

        $flatten = json_encode($json) ?: '';
        $lower = strtolower($flatten);
        if (str_contains($lower, ' plants') || str_contains($lower, ' plant')) {
            $errors[] = 'banned_word:plant';
        }
        if (str_contains($lower, 'm/s') || str_contains($lower, 'ms/cm') || str_contains($lower, 'mS/cm')) {
            $errors[] = 'banned_unit:ms_per_cm';
        }
        if (str_contains($lower, 'calibration')) {
            $errors[] = 'banned_word:calibration';
        }
        // Check for EC mention only if EC sensor is missing
        // Use word boundaries to avoid matching "excellent", "effective", etc.
        if (in_array('ec', $missingSensors, true)) {
            // Check in the actual text content (description, tips, etc.)
            $textContent = ($json['description'] ?? '') . ' ' . 
                          implode(' ', array_column($json['bullet_points'] ?? [], 'heading')) . ' ' .
                          implode(' ', array_merge(...array_column($json['bullet_points'] ?? [], 'tips')));
            // Match "EC" as standalone word (not inside "excellent", "effective", etc.)
            if (preg_match('/\bEC\b/i', $textContent)) {
                $errors[] = 'mentions_ec_but_missing';
            }
        }

        if (isset($json['warnings']) && !is_array($json['warnings'])) {
            $errors[] = 'warnings_not_array';
        }
        if (isset($json['evidence']) && !is_array($json['evidence'])) {
            $errors[] = 'evidence_not_array';
        }
        if (isset($json['evidence']) && is_array($json['evidence'])) {
            foreach ($json['evidence'] as $i => $ev) {
                if (!is_array($ev) || !isset($ev['context_id'], $ev['used_for'])) {
                    $errors[] = "evidence_invalid_at:{$i}";
                }
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * Generate smart recommendations for clean water only
     * Direct, actionable recommendations without RAG
     *
     * @param SensorReading $currentReading The current sensor reading
     * @return array Returns recommendations with severity levels
     */
    public function generateSmartRecommendations(SensorReading $currentReading): array
    {
        try {
            $prompt = $this->buildSmartRecommendationsPrompt($currentReading);

            Log::info('Smart Recommendations: Generating for clean water', [
                'ph' => $currentReading->ph,
                'tds' => $currentReading->tds,
                'turbidity' => $currentReading->turbidity,
                'water_level' => $currentReading->water_level,
            ]);

            $attempt = 0;
            $generationResult = null;
            $validationErrors = [];
            $lastRawOutput = null;

            while ($attempt < self::MAX_GENERATION_ATTEMPTS) {
                $attempt++;
                $generationResult = $this->geminiService->generateContent($prompt);

                if (!$generationResult['success']) {
                    $validationErrors = ['generation_failed: ' . ($generationResult['error'] ?? 'unknown')];
                    $lastRawOutput = $generationResult['raw_output'] ?? null;
                    if (empty($generationResult['raw_output'])) {
                        break;
                    }
                    continue;
                }

                $lastRawOutput = $generationResult['raw_output'] ?? null;
                $candidate = $generationResult['data'] ?? null;
                $validationErrors = $this->validateRecommendationsJson($candidate);
                
                if (empty($validationErrors)) {
                    break;
                }

                $prompt .= "\n\nRETRY_NOTE: Fix these validation errors and output ONLY valid JSON: "
                    . implode('; ', $validationErrors) . "\n";
            }

            if (!$generationResult || !$generationResult['success'] || !empty($validationErrors)) {
                Log::error('Smart Recommendations: Failed after retries', [
                    'attempts' => $attempt,
                    'validation_errors' => $validationErrors,
                    'raw_output' => $lastRawOutput,
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to generate valid recommendations',
                    'validation_errors' => $validationErrors,
                    'attempts' => $attempt,
                    'raw_output' => $lastRawOutput,
                ];
            }

            return [
                'success' => true,
                'recommendations' => $generationResult['data']['recommendations'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Smart Recommendations: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Exception during recommendation generation',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Build prompt for smart recommendations
     *
     * @param SensorReading $reading
     * @return string
     */
    private function buildSmartRecommendationsPrompt(SensorReading $reading): string
    {
        $prompt = <<<PROMPT
You are providing DIRECT, ACTIONABLE water quality recommendations for clean water before it enters a kratky hydroponic system for lettuce.

Current Clean Water Readings:
- pH: {$reading->ph}
- TDS: {$reading->tds} ppm
- Turbidity: {$reading->turbidity} NTU
- Water Level: {$reading->water_level}%

Analyze the readings and provide 1-3 CONCISE recommendations with specific actions.

CRITICAL RULES:
- Output ONLY valid JSON (no markdown)
- Be DIRECT and PRESCRIPTIVE (e.g., "Add 5ml of pH Up solution")
- Use "warning" for minor issues, "critical" for plant-threatening issues, "info" for optimal conditions
- NO explanations, just specific actions
- Max 3 recommendations
- Each recommendation must have: type, issue, action, priority
- Priority 1 is highest (most urgent), 2 is medium, 3 is lowest
- Do NOT use the word "plant" or "plants"; say "lettuce" instead

WATER QUALITY GUIDELINES:
- Optimal pH for lettuce: 5.5-6.5
- Optimal TDS: 560-840 ppm (nutrient solution strength)
- Optimal Turbidity: < 5 NTU (clean water)
- Water Level: > 50% is good, < 30% needs refilling

Output ONLY valid JSON:
{
  "recommendations": [
    {
      "type": "warning|info|critical",
      "issue": "short problem statement",
      "action": "specific action with measurements",
      "priority": 1
    }
  ]
}

Examples of good recommendations:
- {"type": "warning", "issue": "pH is too low", "action": "Add 5ml of pH Up solution", "priority": 1}
- {"type": "critical", "issue": "Water level is critically low", "action": "Refill tank to at least 70%", "priority": 1}
- {"type": "info", "issue": "Water quality is optimal", "action": "Continue monitoring daily", "priority": 3}

No markdown, no extra explanations - just valid JSON.
PROMPT;

        return $prompt;
    }

    /**
     * Validate smart recommendations JSON structure
     *
     * @param mixed $json
     * @return array List of validation errors
     */
    private function validateRecommendationsJson($json): array
    {
        $errors = [];
        
        if (!is_array($json)) {
            return ['not_json_object'];
        }

        if (!array_key_exists('recommendations', $json)) {
            return ['missing_key:recommendations'];
        }

        if (!is_array($json['recommendations'])) {
            return ['recommendations_not_array'];
        }

        if (count($json['recommendations']) > 3) {
            $errors[] = 'max_3_recommendations';
        }

        if (count($json['recommendations']) === 0) {
            $errors[] = 'at_least_1_recommendation';
        }

        foreach ($json['recommendations'] as $i => $rec) {
            if (!is_array($rec)) {
                $errors[] = "recommendation_not_object_at:{$i}";
                continue;
            }

            foreach (['type', 'issue', 'action', 'priority'] as $field) {
                if (!array_key_exists($field, $rec)) {
                    $errors[] = "missing_field:{$field}_at:{$i}";
                }
            }

            if (isset($rec['type']) && !in_array($rec['type'], ['warning', 'info', 'critical'])) {
                $errors[] = "invalid_type_at:{$i}";
            }

            if (isset($rec['priority']) && (!is_int($rec['priority']) || $rec['priority'] < 1)) {
                $errors[] = "invalid_priority_at:{$i}";
            }

            if (isset($rec['issue']) && !is_string($rec['issue'])) {
                $errors[] = "issue_not_string_at:{$i}";
            }

            if (isset($rec['action']) && !is_string($rec['action'])) {
                $errors[] = "action_not_string_at:{$i}";
            }
        }

        $flatten = json_encode($json) ?: '';
        $lower = strtolower($flatten);
        if (str_contains($lower, ' plants') || str_contains($lower, ' plant')) {
            $errors[] = 'banned_word:plant';
        }

        return array_values(array_unique($errors));
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
        
        $readingsText = "- pH: {$currentReading->ph}\n- TDS: {$currentReading->tds} ppm";
        
        // Add turbidity only if NOT hydroponics_water
        if ($systemType !== 'hydroponics_water') {
            $readingsText .= "\n- Turbidity: {$currentReading->turbidity} NTU";
        }
        
        // Add EC only if hydroponics_water
        if ($systemType === 'hydroponics_water' && !is_null($currentReading->ec)) {
            $readingsText .= "\n- EC: {$currentReading->ec}";
        }
        
        $readingsText .= "\n- Water Level: {$currentReading->water_level}";
        
        $prompt = <<<PROMPT
You are an expert in hydroponics and water quality management.
Provide simple, actionable advice based on these {$systemLabel} readings:

{$readingsText}

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

