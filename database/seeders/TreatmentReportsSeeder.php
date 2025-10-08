<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\TreatmentReport;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TreatmentReportsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $devices = Device::all();

        foreach ($devices as $device) {
            TreatmentReport::create([
                'device_id' => $device->id,
                'start_time' => Carbon::now()->subDays(rand(2, 5)),
                'end_time' => Carbon::now(),
                'final_status' => 'success',
                'total_cycles' => rand(3, 6),
            ]);
        }
    }
}
