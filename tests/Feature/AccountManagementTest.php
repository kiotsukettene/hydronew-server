<?php

use App\Models\Device;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);
});

describe('Manage Account', function () {
    it('authenticated user can access manage account endpoint', function () {
        $devices = Device::factory()->count(3)->create();
        foreach ($devices as $device) {
            $device->users()->attach($this->user->id);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/manage-account');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'address',
                    'owned_devices_count',
                    'profile_picture',
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                    'owned_devices_count' => 3,
                ]
            ]);
    });

    it('unauthenticated user cannot access manage account endpoint', function () {
        $response = $this->getJson('/api/v1/manage-account');

        $response->assertStatus(401);
    });
});

describe('Update Account', function () {
    it('authenticated user can update account', function () {
        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'address' => 'New Address 123',
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/update-account', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Account updated successfully.',
            ])
            ->assertJsonFragment([
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'address' => 'New Address 123',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'address' => 'New Address 123',
        ]);
    });

    it('update account fails with invalid input', function () {
        $invalidData = [
            'first_name' => '', // Empty first name
            'last_name' => '', // Empty last name
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/update-account', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name']);
    });

    it('unauthenticated user cannot update account', function () {
        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ];

        $response = $this->putJson('/api/v1/update-account', $updateData);

        $response->assertStatus(401);
    });
});

describe('Update Profile Picture', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('authenticated user can upload profile picture', function () {
        $file = UploadedFile::fake()->image('profile.jpg');

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/update-profile-picture', [
                'profile_picture' => $file,
            ]);

        $response->assertStatus(200);

        $this->user->refresh();
        expect($this->user->profile_picture)->not->toBeNull();
        Storage::disk('public')->assertExists($this->user->profile_picture);
    });

    it('upload fails when no file is provided', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/update-profile-picture', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['profile_picture']);
    });

    it('upload fails with invalid image file', function () {
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/update-profile-picture', [
                'profile_picture' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['profile_picture']);
    });

    it('unauthenticated user cannot upload profile picture', function () {
        $file = UploadedFile::fake()->image('profile.jpg');

        $response = $this->postJson('/api/v1/update-profile-picture', [
            'profile_picture' => $file,
        ]);

        $response->assertStatus(401);
    });
});

describe('Update Password', function () {
    it('authenticated user can update password', function () {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/manage-account/update-password', [
                'current_password' => 'current-password',
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password updated successfully.',
            ]);

        $this->user->refresh();
        expect(Hash::check('NewPassword123!', $this->user->password))->toBeTrue();
    });

    it('password update fails with incorrect current password', function () {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/manage-account/update-password', [
                'current_password' => 'wrong-password',
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Current password is incorrect.',
            ]);

        $this->user->refresh();
        expect(Hash::check('current-password', $this->user->password))->toBeTrue();
    });

    it('password update fails with invalid new password', function () {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/manage-account/update-password', [
                'current_password' => 'current-password',
                'new_password' => '123', // Too short
                'new_password_confirmation' => '123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    });

    it('password update fails when passwords do not match', function () {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/manage-account/update-password', [
                'current_password' => 'current-password',
                'new_password' => 'new-password-123',
                'new_password_confirmation' => 'different-password',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    });

    it('unauthenticated user cannot update password', function () {
        $response = $this->putJson('/api/v1/manage-account/update-password', [
            'current_password' => 'current-password',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(401);
    });
});

describe('Login History', function () {
    beforeEach(function () {
        // Clear all login histories for this user before each test
        $this->user->loginHistories()->delete();
    });

    it('authenticated user can view login history', function () {
        // Create login history records
        $this->user->loginHistories()->createMany([
            [
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => now()->subDays(2),
            ],
            [
                'ip_address' => '192.168.1.2',
                'user_agent' => 'Chrome/90.0',
                'created_at' => now()->subDay(),
            ],
            [
                'ip_address' => '192.168.1.3',
                'user_agent' => 'Safari/14.0',
                'created_at' => now(),
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/manage-account/login-history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ]
            ]);

        expect($response->json('data.total'))->toBe(3);
    });

    it('login history is paginated correctly', function () {
        // Create 25 login history records
        $this->user->loginHistories()->createMany(
            collect(range(1, 25))->map(fn($i) => [
                'ip_address' => "192.168.1.$i",
                'user_agent' => 'Mozilla/5.0',
                'created_at' => now()->subDays($i),
            ])->toArray()
        );

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/manage-account/login-history');

        $response->assertStatus(200);
        
        expect($response->json('data.per_page'))->toBe(10)
            ->and($response->json('data.total'))->toBe(25)
            ->and(count($response->json('data.data')))->toBe(10);
    });


    it('unauthenticated user cannot access login history', function () {
        $response = $this->getJson('/api/v1/manage-account/login-history');

        $response->assertStatus(401);
    });
});