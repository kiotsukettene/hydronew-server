<?php

namespace App\Console\Commands;

use App\Events\NewSensorReadingsAvailable;
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
        $reference = $database->getReference('sensorData');
        $snapshot = $reference->getSnapshot();
        $data = $snapshot->getValue();

        if (!$data) {
            $this->warn('⚠️ No sensor data found in Firebase.');
            return 0;
        }

        // Only process data from the last 5 minutes
        $cutoff = Carbon::now()->subMinutes(5);

        // --- Performance Boost: Pre-fetch last readings to avoid N+1 queries ---
        $latestReadingIds = SensorReading::select(DB::raw('MAX(id) as id'))
            ->groupBy('sensor_id')
            ->pluck('id');

        $lastReadings = SensorReading::whereIn('id', $latestReadingIds)
            ->get()
            ->keyBy('sensor_id');

        $this->info("Pre-fetched " . $lastReadings->count() . " last known sensor readings from DB.");

        $sensorMap = ['ph' => 1, 'tds' => 2, 'turbidity' => 3];
        $newReadingsForDb = [];
        $batchTime = Carbon::now();

        foreach ($data as $reading) {
            if (!isset($reading['timestamp'])) continue;

            $readingTime = Carbon::createFromTimestampMs($reading['timestamp']);

            // Skip data older than 5 minutes
            if ($readingTime->lt($cutoff)) continue;

            foreach ($sensorMap as $type => $sensorId) {
                if (!isset($reading[$type])) continue;

                $newValue = (float) $reading[$type];
                $lastReading = $lastReadings->get($sensorId);

                if ($lastReading) {
                    $valueChanged = abs($lastReading->reading_value - $newValue) >= $this->margin;
                    $intervalPassed = Carbon::parse($lastReading->reading_time)->diffInSeconds($readingTime) >= $this->minInterval;

                    if (!$valueChanged && !$intervalPassed) {
                        continue; // Skip this reading
                    }
                }

                // Queue this reading for a batch insert
                $newReadingsForDb[] = [
                    'sensor_id' => $sensorId,
                    'reading_value' => $newValue,
                    'reading_time' => $readingTime,
                    'created_at' => $batchTime,
                    'updated_at' => $batchTime,
                ];
            }
        }

        if (!empty($newReadingsForDb)) {
            // --- Save all new readings in ONE query ---
            SensorReading::insert($newReadingsForDb);
            $this->info("✅ Saved " . count($newReadingsForDb) . " new sensor readings to the database!");

            // --- Fire the Pusher Event with all the new data ---
            NewSensorReadingsAvailable::dispatch($newReadingsForDb);
            $this->info('📡 Fired Pusher event with new data.');

        } else {
            $this->info('ℹ️ No new readings needed to be saved.');
        }

        $this->info('🔥 Firebase sync complete!');
        return 0;
    }
}

