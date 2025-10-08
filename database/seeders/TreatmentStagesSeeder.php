<?php

namespace Database\Seeders;

use App\Models\TreatmentStage;
use App\Models\TreatmentReport;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TreatmentStagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            ['stage_name' => 'MFC', 'stage_order' => 1],
            ['stage_name' => 'Natural Filter', 'stage_order' => 2],
            ['stage_name' => 'UV Filter', 'stage_order' => 3],
            ['stage_name' => 'Clean Water Tank', 'stage_order' => 4],
        ];

        foreach (TreatmentReport::all() as $report) {
            foreach ($stages as $stage) {
                TreatmentStage::create([
                    'treatment_id' => $report->id,
                    'stage_name' => $stage['stage_name'],
                    'stage_order' => $stage['stage_order'],
                    'status' => 'passed',
                    'pH' => number_format(rand(65, 75) / 10, 1), // e.g. 6.5–7.5
                    'turbidity' => rand(1, 10), // NTU
                    'TDS' => rand(100, 400), // ppm
                    'notes' => 'Stage completed successfully.',
                ]);
            }
        }
    }
}
