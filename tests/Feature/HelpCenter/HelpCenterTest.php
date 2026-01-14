<?php

use App\Models\HelpCenter;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('Password123!'),
    ]);
});

describe('Help Center Listing', function () {
    it('authenticated user can view help center entries', function () {
        HelpCenter::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/help-center');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
                'filters',
            ]);

        expect($response->json('data.total'))->toBe(3)
            ->and($response->json('data.per_page'))->toBe(5);
    });

    it('search filter returns matching results', function () {
        // Create entries with different content
        HelpCenter::factory()->create([
            'question' => 'How to setup hydroponics?',
            'answer' => 'Follow these steps to setup your hydroponic system.',
        ]);

        HelpCenter::factory()->create([
            'question' => 'What is pH level?',
            'answer' => 'pH level measures the acidity or alkalinity of water.',
        ]);

        HelpCenter::factory()->create([
            'question' => 'How to maintain water quality?',
            'answer' => 'Regular monitoring and maintenance is required.',
        ]);

        // Search by question
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/help-center?search=pH');

        $response->assertStatus(200);
        expect($response->json('data.total'))->toBe(1)
            ->and($response->json('data.data.0.question'))->toContain('pH');

        // Search by answer
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/help-center?search=steps');

        $response->assertStatus(200);
        expect($response->json('data.total'))->toBe(1)
            ->and($response->json('data.data.0.answer'))->toContain('steps');
    });

    it('pagination works correctly (5 items per page)', function () {
        // Create 12 help center entries
        HelpCenter::factory()->count(12)->create();

        // First page
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/help-center');

        $response->assertStatus(200);
        expect($response->json('data.per_page'))->toBe(5)
            ->and($response->json('data.total'))->toBe(12)
            ->and($response->json('data.current_page'))->toBe(1)
            ->and($response->json('data.last_page'))->toBe(3)
            ->and(count($response->json('data.data')))->toBe(5);

        // Second page
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/help-center?page=2');

        $response->assertStatus(200);
        expect($response->json('data.current_page'))->toBe(2)
            ->and(count($response->json('data.data')))->toBe(5);

        // Third page (should have 2 items)
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/help-center?page=3');

        $response->assertStatus(200);
        expect($response->json('data.current_page'))->toBe(3)
            ->and(count($response->json('data.data')))->toBe(2);
    });

});
