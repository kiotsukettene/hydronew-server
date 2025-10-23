<?php

namespace App\Console\Commands;

use Kreait\Firebase\Factory;
use App\Models\SensorReading;
use App\Models\Sensor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB; // Import DB for raw queries

class SyncFirebaseData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:sync';
    protected $description = 'Fetch realtime sensor data from Firebase and store it in the database';

    /**
     * Execute the console command.
     */

    protected float $margin = 0.1;
    protected int $minInterval = 300; // 300 seconds = 5 minutes
    public function handle()
    {
        // Get absolute path of your service account file
        $serviceAccount = storage_path('app/firebase/hydronew-iot-firebase-adminsdk-fbsvc-172673f11b.json');

        // Initialize Firebase
        $factory = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->withDatabaseUri('https://hydronew-iot-default-rtdb.asia-southeast1.firebasedatabase.app');

        $database = $factory->createDatabase();

        // --- REFACTOR 1: Server-side filtering ---

        // 1. Get cutoff in MILLISECONDS for Firebase
        $cutoffMs = Carbon::now()->subMinutes(5)->valueOf();

        // 2. Fetch only new data from Firebase using server-side ordering and filtering
        $reference = $database->getReference('sensorData');
        // $query = $reference->orderByChild('timestamp')->startAt($cutoffMs); // THIS LINE CAUSES THE ERROR without an index
        // $snapshot = $query->getSnapshot();
        $snapshot = $reference->getSnapshot(); // Workaround: Fetch all data and filter in PHP
        $data = $snapshot->getValue();

        if (!$data) {
            $this->warn('⚠️ No new sensor data found in the last 5 minutes.');
            return 0;
        }

        // --- REFACTOR 2: Avoid N+1 query problem ---

        // 3. Pre-fetch the latest reading for ALL sensors
        // This runs 2 queries total, instead of 1 query *per reading* inside the loop
        $latestReadingIds = SensorReading::select(DB::raw('MAX(id) as id'))
            ->groupBy('sensor_id')
            ->pluck('id');

        // Fetch those latest readings and key them by sensor_id for easy lookup
        $lastReadings = SensorReading::whereIn('id', $latestReadingIds)
            ->get()
            ->keyBy('sensor_id');

        $this->info("Found " . count($data) . " new Firebase readings.");
        $this->info("Pre-fetched " . $lastReadings->count() . " last known sensor readings from DB.");

        $sensorMap = [
            'ph' => 1,
            'tds' => 2,
            'turbidity' => 3,
        ];

        // --- REFACTOR 3: Batch Inserts ---
        $newReadings = []; // Array to hold all new readings
        $batchTime = Carbon::now(); // Use one timestamp for created_at/updated_at

        foreach ($data as $key => $reading) {

            // Skip if timestamp is missing (should be rare with new query)
            if (!isset($reading['timestamp'])) {
                continue;
            }

            $timestampMs = (int) $reading['timestamp'];

            // --- ADDED: Client-side filtering (workaround for no index) ---
            if ($timestampMs < $cutoffMs) {
                continue; // Skip old data
            }
            // --- END ADDED ---

            // Convert to Carbon object for insertion
            $readingTime = Carbon::createFromTimestampMs($timestampMs);


            foreach ($sensorMap as $type => $sensorId) {
                if (!isset($reading[$type])) {
                    continue;
                }

                $newValue = (float) $reading[$type];

                // 4. Check against our pre-fetched last reading
                $lastReading = $lastReadings->get($sensorId);

                if ($lastReading) {
                    // --- REFACTOR 4: Implement minInterval logic ---
                    // LOGIC: Skip this reading if...
                    // (A) the value hasn't changed significantly
                    // (B) AND it's not time to log a new value anyway (minInterval not passed)

                    $valueChanged = abs($lastReading->reading_value - $newValue) >= $this->margin;

                    // Parse last reading time as Carbon
                    $lastReadingTime = Carbon::parse($lastReading->reading_time);
                    $intervalPassed = $lastReadingTime->diffInSeconds($batchTime) >= $this->minInterval;

                    if (!$valueChanged && !$intervalPassed) {
                        $this->info("⏭️  Skipped {$type} (no change & within {$this->minInterval}s interval)");
                        continue;
                    }
                }

                // If $lastReading is null, or value changed, or interval passed,
                // we queue it for insertion.
                $newReadings[] = [
                    'sensor_id' => $sensorId,
                    'reading_value' => $newValue,
                    'reading_time' => $readingTime,
                    'created_at' => $batchTime, // Manually add timestamps for batch insert
                    'updated_at' => $batchTime,
                ];

                $this->info("👍 Queued new {$type} reading: {$newValue}");
            }
        }

        // --- REFACTOR 5: Perform a single batch insert ---
        if (!empty($newReadings)) {
            SensorReading::insert($newReadings);
            $this->info("✅ Saved " . count($newReadings) . " new sensor readings to the database!");
        } else {
            $this->info("ℹ️ No new readings needed to be saved.");
        }

        $this->info('🔥 Firebase sync complete!');
        return 0;
    }
}

