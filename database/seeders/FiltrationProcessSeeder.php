<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\FiltrationProcess;
use App\Models\TreatmentReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FiltrationProcessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only seed if we have devices in the database
        $devices = Device::all();
        
        if ($devices->isEmpty()) {
            $this->command->warn('⚠ No devices found. Please seed devices first before seeding filtration processes.');
            return;
        }

        $this->command->info('Seeding filtration processes...');

        // Example 1: Active filtration process (Stage 1 in progress)
        $device1 = $devices->first();
        
        $treatmentReport1 = TreatmentReport::create([
            'device_id' => $device1->id,
            'start_time' => now()->subHours(2),
            'end_time' => null,
            'final_status' => 'pending',
            'total_cycles' => null,
        ]);

        FiltrationProcess::create([
            'device_id' => $device1->id,
            'treatment_report_id' => $treatmentReport1->id,
            'status' => 'active',
            'pump_3_state' => true,
            'valve_1_state' => false,
            'valve_2_state' => false,
            'stage_1_started_at' => now()->subHours(2),
            'stages_2_4_started_at' => null,
            'restart_count' => 0,
        ]);

        $this->command->info("✓ Created active filtration process for device: {$device1->serial_number}");

        // Example 2: Completed filtration process (if we have more than 1 device)
        if ($devices->count() > 1) {
            $device2 = $devices->skip(1)->first();
            
            $treatmentReport2 = TreatmentReport::create([
                'device_id' => $device2->id,
                'start_time' => now()->subDays(1),
                'end_time' => now()->subDays(1)->addHours(1),
                'final_status' => 'success',
                'total_cycles' => 1,
            ]);

            FiltrationProcess::create([
                'device_id' => $device2->id,
                'treatment_report_id' => $treatmentReport2->id,
                'status' => 'completed',
                'pump_3_state' => false,
                'valve_1_state' => false,
                'valve_2_state' => false,
                'stage_1_started_at' => now()->subDays(1),
                'stages_2_4_started_at' => now()->subDays(1)->addMinutes(30),
                'restart_count' => 0,
            ]);

            $this->command->info("✓ Created completed filtration process for device: {$device2->serial_number}");
        }

        // Example 3: Filtration process with restart (if we have more than 2 devices)
        if ($devices->count() > 2) {
            $device3 = $devices->skip(2)->first();
            
            $treatmentReport3 = TreatmentReport::create([
                'device_id' => $device3->id,
                'start_time' => now()->subHours(3),
                'end_time' => null,
                'final_status' => 'pending',
                'total_cycles' => null,
            ]);

            FiltrationProcess::create([
                'device_id' => $device3->id,
                'treatment_report_id' => $treatmentReport3->id,
                'status' => 'active',
                'pump_3_state' => true,
                'valve_1_state' => false,
                'valve_2_state' => false,
                'stage_1_started_at' => now()->subHours(3),
                'stages_2_4_started_at' => now()->subHours(2),
                'restart_count' => 2, // This process has been restarted twice
            ]);

            $this->command->info("✓ Created filtration process with restarts for device: {$device3->serial_number}");
        }

        $this->command->newLine();
        $this->command->info(' Filtration process seeding completed!');
        $this->command->info("Total filtration processes created: " . FiltrationProcess::count());
    }
}
