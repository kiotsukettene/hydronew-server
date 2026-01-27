<?php

use App\Models\Device;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('Password123!'),
        'email_verified_at' => now(),
    ]);

    $this->otherUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Create device for user
    $this->device = Device::factory()->create(['is_archived' => false]);
    $this->device->users()->attach($this->user->id);

    // Create device for other user
    $this->otherDevice = Device::factory()->create(['is_archived' => false]);
    $this->otherDevice->users()->attach($this->otherUser->id);
});

describe('Submit Feedback', function () {
    it('authenticated user can submit feedback successfully', function () {
        $feedbackData = [
            'category' => 'bug_report',
            'subject' => 'pH sensor not reading correctly',
            'message' => 'The pH sensor shows incorrect readings after calibration. Expected 7.0 but showing 6.2.',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'device_id',
                    'category',
                    'subject',
                    'message',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Feedback submitted successfully. We appreciate your input!',
                'data' => [
                    'user_id' => $this->user->id,
                    'device_id' => $this->device->id,
                    'category' => 'bug_report',
                    'subject' => 'pH sensor not reading correctly',
                    'message' => 'The pH sensor shows incorrect readings after calibration. Expected 7.0 but showing 6.2.',
                ],
            ]);

        $this->assertDatabaseHas('feedback', [
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
            'category' => 'bug_report',
            'subject' => 'pH sensor not reading correctly',
            'message' => 'The pH sensor shows incorrect readings after calibration. Expected 7.0 but showing 6.2.',
        ]);
    });

    it('device_id is automatically detected from user paired device', function () {
        $feedbackData = [
            'category' => 'general_feedback',
            'message' => 'Great product! Very easy to use.',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(201);

        expect($response->json('data.device_id'))->toBe($this->device->id);
    });

    it('user can submit feedback without subject (optional field)', function () {
        $feedbackData = [
            'category' => 'feature_request',
            'message' => 'Would love to see automated notifications when pH levels are too high.',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'category' => 'feature_request',
                    'message' => 'Would love to see automated notifications when pH levels are too high.',
                ],
            ]);
        
        // Verify subject is not required
        expect($response->json('data'))->toHaveKey('category');
    });

    it('returns error if user has no paired device', function () {
        // Create user without device
        $userWithoutDevice = User::factory()->create(['email_verified_at' => now()]);

        $feedbackData = [
            'category' => 'bug_report',
            'message' => 'This should fail because user has no device.',
        ];

        $response = $this->actingAs($userWithoutDevice)
            ->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'No device found. Please pair a device before submitting feedback.',
            ]);
    });

    it('ignores archived devices when detecting user device', function () {
        // Archive the original device
        $this->device->update(['is_archived' => true]);
        
        // Detach it from user
        $this->device->users()->detach($this->user->id);
        
        // Create a new non-archived device 
        $activeDevice = Device::factory()->create(['is_archived' => false]);
        $activeDevice->users()->attach($this->user->id);

        $feedbackData = [
            'category' => 'general_feedback',
            'message' => 'Testing with active device.',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(201);
        expect($response->json('data.device_id'))->toBe($activeDevice->id);
    });

    it('unauthenticated user cannot submit feedback', function () {
        $feedbackData = [
            'category' => 'bug_report',
            'message' => 'This should fail without authentication.',
        ];

        $response = $this->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(401);
    });
});

describe('Validation Rules', function () {
    it('category field is required', function () {
        $feedbackData = [
            'message' => 'Message without category.',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    });

    it('category must be valid enum value', function () {
        $feedbackData = [
            'category' => 'invalid_category',
            'message' => 'Testing invalid category.',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    });

    it('accepts all valid category values', function () {
        $validCategories = ['bug_report', 'feature_request', 'general_feedback', 'device_issue', 'other'];

        foreach ($validCategories as $category) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/feedback', [
                    'category' => $category,
                    'message' => "Testing category: {$category}",
                ]);

            $response->assertStatus(201);
        }
    });

    it('message field is required', function () {
        $feedbackData = [
            'category' => 'bug_report',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    });

    it('message must be at least 10 characters', function () {
        $feedbackData = [
            'category' => 'bug_report',
            'message' => 'Too short',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    });

    it('message cannot exceed 2000 characters', function () {
        $feedbackData = [
            'category' => 'bug_report',
            'message' => str_repeat('a', 2001),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    });

    it('message with exactly 2000 characters is valid', function () {
        $feedbackData = [
            'category' => 'bug_report',
            'message' => str_repeat('a', 2000),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(201);
    });

    it('subject is optional but cannot exceed 255 characters', function () {
        $feedbackData = [
            'category' => 'bug_report',
            'subject' => str_repeat('a', 256),
            'message' => 'Testing subject length validation.',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject']);
    });

    it('subject with exactly 255 characters is valid', function () {
        $feedbackData = [
            'category' => 'bug_report',
            'subject' => str_repeat('a', 255),
            'message' => 'Testing subject maximum length.',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $feedbackData);

        $response->assertStatus(201);
    });
});

describe('View Feedback History', function () {
    it('authenticated user can view their own feedback', function () {
        // Create feedback for authenticated user
        Feedback::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        // Create feedback for another user (should not appear)
        Feedback::factory()->count(2)->create([
            'user_id' => $this->otherUser->id,
            'device_id' => $this->otherDevice->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'device_id',
                        'category',
                        'subject',
                        'message',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'has_more',
                'total',
                'offset',
                'limit',
            ]);

        expect(count($response->json('data')))->toBe(3)
            ->and($response->json('total'))->toBe(3);

        // Verify all feedback belongs to authenticated user
        foreach ($response->json('data') as $feedback) {
            expect($feedback['user_id'])->toBe($this->user->id);
        }
    });

    it('user cannot see other users feedback', function () {
        // Create feedback for other user
        Feedback::factory()->count(5)->create([
            'user_id' => $this->otherUser->id,
            'device_id' => $this->otherDevice->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback');

        $response->assertStatus(200);

        expect(count($response->json('data')))->toBe(0)
            ->and($response->json('total'))->toBe(0);
    });

    it('feedback is ordered by newest first', function () {
        $oldFeedback = Feedback::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
            'created_at' => now()->subDays(5),
        ]);

        $newFeedback = Feedback::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data[0]['id'])->toBe($newFeedback->id)
            ->and($data[1]['id'])->toBe($oldFeedback->id);
    });

    it('includes device_id with each feedback', function () {
        Feedback::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback');

        $response->assertStatus(200);

        $feedback = $response->json('data')[0];
        expect($feedback['device_id'])->toBe($this->device->id);
    });

    it('unauthenticated user cannot view feedback', function () {
        $response = $this->getJson('/api/v1/feedback');

        $response->assertStatus(401);
    });
});

describe('Pagination', function () {
    it('default pagination returns 20 items per page', function () {
        // Create 25 feedback items
        Feedback::factory()->count(25)->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback');

        $response->assertStatus(200);

        expect(count($response->json('data')))->toBe(20)
            ->and($response->json('limit'))->toBe(20)
            ->and($response->json('offset'))->toBe(0)
            ->and($response->json('total'))->toBe(25)
            ->and($response->json('has_more'))->toBeTrue();
    });

    it('can specify custom limit', function () {
        Feedback::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback?limit=5');

        $response->assertStatus(200);

        expect(count($response->json('data')))->toBe(5)
            ->and((int)$response->json('limit'))->toBe(5)
            ->and($response->json('has_more'))->toBeTrue();
    });

    it('can specify offset for pagination', function () {
        // Create feedback with known order
        Feedback::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $firstPage = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback?limit=5&offset=0');

        $secondPage = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback?limit=5&offset=5');

        $firstPage->assertStatus(200);
        $secondPage->assertStatus(200);

        expect(count($firstPage->json('data')))->toBe(5)
            ->and(count($secondPage->json('data')))->toBe(5);

        // Verify different results
        $firstIds = collect($firstPage->json('data'))->pluck('id')->toArray();
        $secondIds = collect($secondPage->json('data'))->pluck('id')->toArray();
        expect($firstIds)->not->toBe($secondIds);
    });

    it('has_more is false on last page', function () {
        Feedback::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback?limit=10&offset=10');

        $response->assertStatus(200);

        expect(count($response->json('data')))->toBe(5)
            ->and($response->json('has_more'))->toBeFalse();
    });

    it('returns empty array when offset exceeds total', function () {
        Feedback::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback?offset=100');

        $response->assertStatus(200);

        expect(count($response->json('data')))->toBe(0)
            ->and($response->json('total'))->toBe(5)
            ->and($response->json('has_more'))->toBeFalse();
    });
});

describe('Device Filtering', function () {
    it('can filter feedback by device_id', function () {
        // Create second device for user
        $secondDevice = Device::factory()->create(['is_archived' => false]);
        $secondDevice->users()->attach($this->user->id);

        // Create feedback for first device
        Feedback::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        // Create feedback for second device
        Feedback::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'device_id' => $secondDevice->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback?device_id=' . $this->device->id);

        $response->assertStatus(200);

        expect(count($response->json('data')))->toBe(3)
            ->and($response->json('total'))->toBe(3);

        // Verify all feedback is for the filtered device
        foreach ($response->json('data') as $feedback) {
            expect($feedback['device_id'])->toBe($this->device->id);
        }
    });

    it('device filter respects user ownership', function () {
        // Create feedback for other user device
        Feedback::factory()->count(5)->create([
            'user_id' => $this->otherUser->id,
            'device_id' => $this->otherDevice->id,
        ]);

        // Try to filter by other user's device
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback?device_id=' . $this->otherDevice->id);

        $response->assertStatus(200);

        // Should return empty because user doesn't own that device's feedback
        expect(count($response->json('data')))->toBe(0);
    });
});

describe('Integration Tests', function () {
    it('complete workflow: submit feedback and view in history', function () {
        $feedbackData = [
            'category' => 'device_issue',
            'subject' => 'Connection problems',
            'message' => 'Device keeps disconnecting from WiFi network every 30 minutes.',
        ];

        // Submit feedback
        $submitResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $feedbackData);

        $submitResponse->assertStatus(201);

        $feedbackId = $submitResponse->json('data.id');

        // View feedback history
        $listResponse = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback');

        $listResponse->assertStatus(200);

        $feedback = collect($listResponse->json('data'))->firstWhere('id', $feedbackId);

        expect($feedback)->not->toBeNull()
            ->and($feedback['category'])->toBe('device_issue')
            ->and($feedback['subject'])->toBe('Connection problems')
            ->and($feedback['message'])->toBe('Device keeps disconnecting from WiFi network every 30 minutes.');
    });

    it('multiple users can submit feedback independently', function () {
        // User 1 submits feedback
        $user1Feedback = [
            'category' => 'bug_report',
            'message' => 'User 1 feedback message.',
        ];

        $this->actingAs($this->user)
            ->postJson('/api/v1/feedback', $user1Feedback)
            ->assertStatus(201);

        // User 2 submits feedback
        $user2Feedback = [
            'category' => 'feature_request',
            'message' => 'User 2 feedback message.',
        ];

        $this->actingAs($this->otherUser)
            ->postJson('/api/v1/feedback', $user2Feedback)
            ->assertStatus(201);

        // User 1 can only see their own feedback
        $user1Response = $this->actingAs($this->user)
            ->getJson('/api/v1/feedback');

        $user1Response->assertStatus(200);
        expect($user1Response->json('total'))->toBe(1);
        expect($user1Response->json('data')[0]['message'])->toBe('User 1 feedback message.');

        // User 2 can only see their own feedback
        $user2Response = $this->actingAs($this->otherUser)
            ->getJson('/api/v1/feedback');

        $user2Response->assertStatus(200);
        expect($user2Response->json('total'))->toBe(1);
        expect($user2Response->json('data')[0]['message'])->toBe('User 2 feedback message.');
    });
});
