<?php

namespace Database\Seeders;

use App\Models\HelpCenter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HelpCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $helpItems = [
            [
                'question' => 'What is Hydronew and how does it work?',
                'answer' => 'Hydronew is an AI-enabled IoT wastewater treatment and hydroponic farming system that uses Microbial Fuel Cells (MFCs) to treat organic greywater. It integrates natural and UV filtration and reuses the treated water for hydroponic crop production while generating small amounts of electricity through microbial activity.',
            ],
            [
                'question' => 'What is Microbial Fuel Cell (MFC)?',
                'answer' => 'A Microbial Fuel Cell (MFC) is a bio-electrochemical system that uses microorganisms to convert organic matter in wastewater into electrical energy while simultaneously breaking down pollutants and purifying the water.',
            ],
            [
                'question' => 'Why is the Microbial Fuel Cell (MFC) part of the filtration process?',
                'answer' => 'The MFC plays a dual role in the filtration process. It biologically treats wastewater by using microorganisms that break down organic matter, reducing contaminants and improving water quality. At the same time, the microbial activity generates electrical energy, which can be used to monitor microbial health and support low-energy system components, making the process sustainable and self-powered.',
            ],
            [
                'question' => 'What type of wastewater can the system treat?',
                'answer' => 'The system is designed to treat organic greywater, which comes from sources like sinks, dirty fruits, and vegetables. It does not support wastewater containing soaps, detergents, industrial effluents, or chemical contaminants that could harm the bacteria in the MFC.',
            ],
            [
                'question' => 'What plants can I grow using Hydronew?',
                'answer' => 'Hydronew is optimized for fast-growing leafy vegetables such as lettuce, spinach, and kale. The current study focuses on post-germinated lettuce seedlings, making it ideal for short growth cycles and efficient nutrient absorption.',
            ],
            [
                'question' => 'How does the AI model improve the system’s performance?',
                'answer' => 'The AI component uses a Random Forest Algorithm to analyze sensor data and predict treatment efficiency, and predict the yield of the hydroponic. This helps in optimizing system performance, ensuring consistent water quality and stable hydroponic growth.',
            ],
            [
                'question' => 'What sensors are used in the IoT monitoring system?',
                'answer' => 'The IoT module includes sensors that monitor water pH, turbidity, temperature, total dissolved solids (TDS), and electric current. These sensors continuously send data to the mobile application for real-time tracking and analytics.',
            ],
            [
                'question' => 'Can I monitor the system remotely?',
                'answer' => 'Yes. The mobile application allows you to monitor real-time water quality data, system status, and hydroponic performance remotely. Notifications are also sent to alert you about important updates or issues requiring attention.',
            ],
            [
                'question' => 'How often should the filters be maintained or replaced?',
                'answer' => 'Natural filters like sand and gravel should be cleaned every 1–2 months to maintain flow efficiency. The UV filter should be cleaned monthly and replaced every 6–12 months depending on water quality and usage.',
            ],
            [
                'question' => 'What happens if there is no internet connection?',
                'answer' => 'HydroNew is currently effective with internet connection. It does not support local saving yet.',
            ],
            [
                'question' => 'Is Hydronew environmentally safe?',
                'answer' => 'Absolutely. Hydronew promotes sustainable wastewater reuse and reduces water waste through its closed-loop system. The combination of MFC, natural filtration, and UV sterilization ensures that treated water is safe for hydroponic irrigation.',
            ],
            [
                'question' => 'What types of plants can I grow in this system?',
                'answer' => 'This system is designed exclusively for growing lettuce. It is optimized for various fast-growing, leafy varieties such as Olmetie, Green Rapid, Romaine, Butterhead, and Loose-leaf types, which perform best in this hydroponic setup. ',
            ],
        ];

        foreach ($helpItems as $item) {
            HelpCenter::create($item);
        }
    }
}
