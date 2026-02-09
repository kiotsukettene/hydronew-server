<?php

namespace App\Console\Commands;

use App\Models\SensorReading;
use App\Models\SensorSystem;
use App\Services\GeminiApiService;
use App\Services\VectorStoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmbedSensorPatterns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embeddings:generate 
                            {--days=30 : Number of days to look back}
                            {--system-type= : Filter by system type (dirty_water, clean_water, hydroponics_water)}
                            {--device-id= : Filter by device ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate embeddings from historical sensor reading patterns';

    private GeminiApiService $geminiService;
    private VectorStoreService $vectorStore;

    public function __construct(GeminiApiService $geminiService, VectorStoreService $vectorStore)
    {
        parent::__construct();
        $this->geminiService = $geminiService;
        $this->vectorStore = $vectorStore;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $systemType = $this->option('system-type');
        $deviceId = $this->option('device-id');

        $this->info("Generating embeddings for sensor patterns...");
        $this->info("Looking back: {$days} days");
        
        if ($systemType) {
            $this->info("System type filter: {$systemType}");
        }
        if ($deviceId) {
            $this->info("Device ID filter: {$deviceId}");
        }

        // Get date range
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays($days);

        // Build query for sensor systems
        $systemsQuery = SensorSystem::where('is_active', true);
        
        if ($systemType) {
            $systemsQuery->where('system_type', $systemType);
        }
        if ($deviceId) {
            $systemsQuery->where('device_id', $deviceId);
        }

        $systems = $systemsQuery->get();

        if ($systems->isEmpty()) {
            $this->error('No sensor systems found matching the criteria.');
            return 1;
        }

        $this->info("Found {$systems->count()} sensor system(s) to process");

        $totalProcessed = 0;
        $totalGenerated = 0;
        $totalFailed = 0;

        // Process each sensor system
        foreach ($systems as $system) {
            $this->line("\nProcessing: {$system->name} (ID: {$system->id}, Type: {$system->system_type})");

            // Get readings for this system in the date range
            $readings = SensorReading::where('sensor_system_id', $system->id)
                ->whereBetween('reading_time', [$startDate, $endDate])
                ->orderBy('reading_time', 'asc')
                ->get();

            if ($readings->isEmpty()) {
                $this->warn("  No readings found for this system");
                continue;
            }

            $this->info("  Found {$readings->count()} readings");

            // Group readings by day
            $dailyGroups = $readings->groupBy(function ($reading) {
                return Carbon::parse($reading->reading_time)->format('Y-m-d');
            });

            $bar = $this->output->createProgressBar($dailyGroups->count());
            $bar->start();

            // Process each daily group
            foreach ($dailyGroups as $date => $dayReadings) {
                $totalProcessed++;

                // Aggregate daily statistics
                $pattern = $this->aggregateDailyPattern($dayReadings, $date, $system);

                // Generate embedding
                $embeddingResult = $this->geminiService->generateEmbedding($pattern['pattern_text']);

                if (!$embeddingResult['success']) {
                    $totalFailed++;
                    $this->newLine();
                    $errorMsg = $embeddingResult['error'] ?? 'Unknown error';
                    $details = $embeddingResult['message'] ?? $embeddingResult['details'] ?? '';
                    $this->error("  Failed to generate embedding for {$date}: {$errorMsg}");
                    if ($details) {
                        $this->line("    Details: {$details}");
                    }
                    $bar->advance();
                    continue;
                }

                // Store in vector database
                $stored = $this->vectorStore->store([
                    'device_id' => $system->device_id,
                    'system_type' => $system->system_type,
                    'pattern_text' => $pattern['pattern_text'],
                    'embedding' => $embeddingResult['embedding'],
                    'embedding_model' => $embeddingResult['model'],
                    'embedding_dim' => $embeddingResult['dimensions'],
                    'period_start' => $pattern['period_start'],
                    'period_end' => $pattern['period_end'],
                    'metadata' => $pattern['metadata'],
                ]);

                if ($stored) {
                    $totalGenerated++;
                } else {
                    $totalFailed++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        // Summary
        $this->newLine();
        $this->info("=== Summary ===");
        $this->info("Daily patterns processed: {$totalProcessed}");
        $this->info("Embeddings generated: {$totalGenerated}");
        if ($totalFailed > 0) {
            $this->warn("Failed: {$totalFailed}");
        }

        // Show vector store stats
        $stats = $this->vectorStore->getStats();
        $this->newLine();
        $this->info("=== Vector Store Stats ===");
        $this->info("Total patterns: {$stats['total_patterns']}");
        $this->info("By system type: " . json_encode($stats['by_system_type']));

        return 0;
    }

    /**
     * Aggregate daily sensor readings into a pattern
     *
     * @param \Illuminate\Support\Collection $readings
     * @param string $date
     * @param SensorSystem $system
     * @return array
     */
    private function aggregateDailyPattern($readings, string $date, SensorSystem $system): array
    {
        $count = $readings->count();

        // Calculate averages and ranges
        $avgPh = round($readings->avg('ph'), 2);
        $minPh = round($readings->min('ph'), 2);
        $maxPh = round($readings->max('ph'), 2);

        $avgTds = round($readings->avg('tds'), 2);
        $minTds = round($readings->min('tds'), 2);
        $maxTds = round($readings->max('tds'), 2);

        $avgTurbidity = round($readings->avg('turbidity'), 2);
        $avgEc = round($readings->avg('ec'), 2);

        // Classification statistics
        $classifiedReadings = $readings->whereNotNull('ai_classification');
        $goodCount = $classifiedReadings->where('ai_classification', 'good')->count();
        $badCount = $classifiedReadings->where('ai_classification', 'bad')->count();
        $totalClassified = $classifiedReadings->count();

        $goodPct = $totalClassified > 0 ? round(($goodCount / $totalClassified) * 100, 2) : 0;
        $badPct = $totalClassified > 0 ? round(($badCount / $totalClassified) * 100, 2) : 0;

        $avgConfidence = $classifiedReadings->whereNotNull('confidence')->avg('confidence');
        $avgConfidence = $avgConfidence ? round($avgConfidence, 2) : null;

        // Count anomalies (out of safe range)
        $anomalyCount = $readings->filter(function ($reading) {
            $phOutOfRange = $reading->ph < 6.0 || $reading->ph > 8.0;
            $tdsOutOfRange = $reading->tds < 560 || $reading->tds > 840;
            $turbidityHigh = $reading->turbidity > 5;
            $ecOutOfRange = $reading->ec < 1.2 || $reading->ec > 2.5;
            
            return $phOutOfRange || $tdsOutOfRange || $turbidityHigh || $ecOutOfRange;
        })->count();

        // Build natural language summary
        $systemLabel = ucfirst(str_replace('_', ' ', $system->system_type));
        $patternText = "{$systemLabel} on {$date}: ";
        $patternText .= "Average pH {$avgPh} (range {$minPh}-{$maxPh}), ";
        $patternText .= "TDS {$avgTds} ppm (range {$minTds}-{$maxTds}), ";
        $patternText .= "turbidity {$avgTurbidity} NTU, ";
        $patternText .= "EC {$avgEc} mS/cm. ";

        if ($totalClassified > 0) {
            $patternText .= "AI classified {$goodPct}% as good, {$badPct}% as bad";
            if ($avgConfidence) {
                $confidencePct = round($avgConfidence * 100);
                $patternText .= " (avg confidence {$confidencePct}%)";
            }
            $patternText .= ". ";
        }

        if ($anomalyCount > 0) {
            $patternText .= "{$anomalyCount} out-of-range readings detected.";
        }

        return [
            'pattern_text' => $patternText,
            'period_start' => Carbon::parse($date)->startOfDay(),
            'period_end' => Carbon::parse($date)->endOfDay(),
            'metadata' => [
                'readings_count' => $count,
                'avg_ph' => $avgPh,
                'min_ph' => $minPh,
                'max_ph' => $maxPh,
                'avg_tds' => $avgTds,
                'min_tds' => $minTds,
                'max_tds' => $maxTds,
                'avg_turbidity' => $avgTurbidity,
                'avg_ec' => $avgEc,
                'classification_good_pct' => $goodPct,
                'classification_bad_pct' => $badPct,
                'avg_confidence' => $avgConfidence,
                'anomaly_count' => $anomalyCount,
            ],
        ];
    }
}
