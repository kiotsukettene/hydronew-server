<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['bug_report', 'feature_request', 'general_feedback', 'device_issue', 'other'];
        
        $subjects = [
            'bug_report' => ['Sensor malfunction', 'pH reading incorrect', 'Connection issues', 'Display not working'],
            'feature_request' => ['Add notification settings', 'Export data feature', 'Mobile app improvements', 'Automation schedules'],
            'general_feedback' => ['Great product!', 'Easy to use', 'Very helpful', 'Impressed with quality'],
            'device_issue' => ['WiFi disconnects', 'Power supply problems', 'Hardware damage', 'Calibration needed'],
            'other' => ['Question about warranty', 'Need technical support', 'General inquiry', 'Documentation request'],
        ];

        $messages = [
            'bug_report' => 'I noticed an issue with the system when trying to perform this operation. It would be great if this could be fixed.',
            'feature_request' => 'It would be really helpful to have this feature implemented. I believe it would improve the user experience significantly.',
            'general_feedback' => 'I wanted to share my positive experience with the product. Overall, I am very satisfied with how it works.',
            'device_issue' => 'The device is experiencing some technical difficulties that need attention. Please advise on the next steps.',
            'other' => 'I have a question regarding the product and would appreciate some clarification on this matter.',
        ];

        $category = $this->faker->randomElement($categories);

        return [
            'user_id' => \App\Models\User::factory(),
            'device_id' => \App\Models\Device::factory(),
            'category' => $category,
            'subject' => $this->faker->optional(0.7)->randomElement($subjects[$category]),
            'message' => $this->faker->optional(0.8)->paragraph() ?? $messages[$category],
        ];
    }
}
