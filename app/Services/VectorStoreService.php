<?php

namespace App\Services;

use App\Models\WaterPatternEmbedding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VectorStoreService
{
    /**
     * Store a pattern with its embedding
     *
     * @param array $patternData Contains: device_id, system_type, pattern_text, embedding, embedding_model, embedding_dim, period_start, period_end, metadata
     * @return WaterPatternEmbedding|null
     */
    public function store(array $patternData): ?WaterPatternEmbedding
    {
        try {
            // Idempotent write: avoid duplicating the same pattern window
            return WaterPatternEmbedding::updateOrCreate(
                [
                    'device_id' => $patternData['device_id'],
                    'system_type' => $patternData['system_type'],
                    'period_start' => $patternData['period_start'],
                    'period_end' => $patternData['period_end'],
                ],
                [
                    'pattern_text' => $patternData['pattern_text'],
                    'embedding' => $patternData['embedding'],
                    'embedding_model' => $patternData['embedding_model'],
                    'embedding_dim' => $patternData['embedding_dim'],
                    'metadata' => $patternData['metadata'],
                ]
            );
        } catch (\Exception $e) {
            Log::error('VectorStore: Failed to store pattern', [
                'error' => $e->getMessage(),
                'pattern_data' => $patternData
            ]);
            return null;
        }
    }

    /**
     * Search for similar patterns using cosine similarity
     *
     * @param array $queryEmbedding The normalized query embedding vector
     * @param string $systemType Filter by system type (dirty_water, clean_water, hydroponics_water)
     * @param int $topK Number of top results to return
     * @param int|null $deviceId Optional device filter
     * @param int $daysBack Number of days to look back (default: 90)
     * @return array Array of results with similarity scores
     */
    public function search(
        array $queryEmbedding,
        string $systemType,
        int $topK = 3,
        ?int $deviceId = null,
        int $daysBack = 90
    ): array {
        try {
            // Build query with filters
            $query = WaterPatternEmbedding::where('system_type', $systemType)
                ->where('period_end', '>=', now()->subDays($daysBack));

            if ($deviceId !== null) {
                $query->where('device_id', $deviceId);
            }

            // Limit candidates for performance
            $candidates = $query->orderBy('period_end', 'desc')
                ->limit(500)
                ->get();

            if ($candidates->isEmpty()) {
                Log::info('VectorStore: No candidates found', [
                    'system_type' => $systemType,
                    'device_id' => $deviceId,
                    'days_back' => $daysBack
                ]);
                return [];
            }

            // Calculate cosine similarity for each candidate
            $results = [];
            foreach ($candidates as $candidate) {
                $storedEmbedding = $candidate->embedding;
                
                // Calculate cosine similarity (dot product of normalized vectors)
                $similarity = $this->cosineSimilarity($queryEmbedding, $storedEmbedding);
                $reliability = (float) ($candidate->metadata['reliability_score'] ?? 1.0);
                $adjusted = $similarity * max(0.0, min(1.0, $reliability));

                $results[] = [
                    'id' => $candidate->id,
                    'similarity_score' => round($similarity, 4),
                    'adjusted_score' => round($adjusted, 4),
                    'period_start' => $candidate->period_start->toDateTimeString(),
                    'period_end' => $candidate->period_end->toDateTimeString(),
                    'pattern_text' => $candidate->pattern_text,
                    'metadata' => $candidate->metadata,
                    'device_id' => $candidate->device_id,
                ];
            }

            // Sort by adjusted score (descending) and return top K
            usort($results, fn($a, $b) => $b['adjusted_score'] <=> $a['adjusted_score']);

            return array_slice($results, 0, $topK);

        } catch (\Exception $e) {
            Log::error('VectorStore: Search failed', [
                'error' => $e->getMessage(),
                'system_type' => $systemType,
                'device_id' => $deviceId
            ]);
            return [];
        }
    }

    /**
     * Calculate cosine similarity between two normalized vectors
     * For normalized vectors, cosine similarity = dot product
     *
     * @param array $vector1 First normalized vector
     * @param array $vector2 Second normalized vector
     * @return float Similarity score between -1 and 1 (typically 0 to 1 for normalized vectors)
     */
    private function cosineSimilarity(array $vector1, array $vector2): float
    {
        if (count($vector1) !== count($vector2)) {
            Log::warning('VectorStore: Vector dimension mismatch', [
                'vector1_dim' => count($vector1),
                'vector2_dim' => count($vector2)
            ]);
            return 0.0;
        }

        // Dot product of normalized vectors
        $dotProduct = 0.0;
        for ($i = 0; $i < count($vector1); $i++) {
            $dotProduct += $vector1[$i] * $vector2[$i];
        }

        return $dotProduct;
    }

    /**
     * Get statistics about stored embeddings
     *
     * @return array Statistics about the vector store
     */
    public function getStats(): array
    {
        return [
            'total_patterns' => WaterPatternEmbedding::count(),
            'by_system_type' => WaterPatternEmbedding::select('system_type', DB::raw('count(*) as count'))
                ->groupBy('system_type')
                ->pluck('count', 'system_type')
                ->toArray(),
            'by_device' => WaterPatternEmbedding::select('device_id', DB::raw('count(*) as count'))
                ->groupBy('device_id')
                ->pluck('count', 'device_id')
                ->toArray(),
            'oldest_pattern' => WaterPatternEmbedding::min('period_start'),
            'newest_pattern' => WaterPatternEmbedding::max('period_end'),
        ];
    }
}

