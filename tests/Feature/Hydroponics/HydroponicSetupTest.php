<?php

use App\Models\Device;
use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use App\Models\HydroponicYieldGrade;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('Password123!'),
    ]);

    $this->otherUser = User::factory()->create();
    
    $this->device = Device::factory()->create();
    
    // Attach the device to the user (many-to-many relationship)
    $this->user->devices()->attach($this->device->id);
});

describe('List Hydroponic Setups', function () {
    it('authenticated user can view their active setups', function () {
        // Create active setups for the user
        HydroponicSetup::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'not_harvested',
            'is_archived' => false,
            'status' => 'active',
            'setup_date' => now()->subDays(10),
            'harvest_date' => now()->addDays(20),
        ]);

        // Create setups that should not appear (different user, archived, harvested, inactive)
        HydroponicSetup::factory()->create([
            'user_id' => $this->otherUser->id,
            'harvest_status' => 'not_harvested',
            'is_archived' => false,
            'status' => 'active',
        ]);

        HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'harvested',
            'is_archived' => false,
            'status' => 'active',
        ]);

        HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'not_harvested',
            'is_archived' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-setups');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJson([
                'status' => 'success',
            ]);

        expect($response->json('data.total'))->toBe(3)
            ->and($response->json('data.per_page'))->toBe(5);
    });

    it('pagination works correctly', function () {
        // Create 12 active setups
        HydroponicSetup::factory()->count(12)->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'not_harvested',
            'is_archived' => false,
            'status' => 'active',
            'setup_date' => now()->subDays(10),
            'harvest_date' => now()->addDays(20),
        ]);

        // First page
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-setups');

        $response->assertStatus(200);
        expect($response->json('data.per_page'))->toBe(5)
            ->and($response->json('data.total'))->toBe(12)
            ->and($response->json('data.current_page'))->toBe(1)
            ->and(count($response->json('data.data')))->toBe(5);

        // Second page
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-setups?page=2');

        $response->assertStatus(200);
        expect($response->json('data.current_page'))->toBe(2)
            ->and(count($response->json('data.data')))->toBe(5);

        // Third page (should have 2 items)
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-setups?page=3');

        $response->assertStatus(200);
        expect($response->json('data.current_page'))->toBe(3)
            ->and(count($response->json('data.data')))->toBe(2);
    });

    it('growth_percentage, plant_age, and days_left are returned', function () {
        $setupDate = now()->subDays(10);
        $harvestDate = now()->addDays(20);
        $expectedPlantAge = (int) $setupDate->diffInDays(now());
        $expectedDaysLeft = max(0, (int) now()->diffInDays($harvestDate, false));

        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'not_harvested',
            'is_archived' => false,
            'status' => 'active',
            'setup_date' => $setupDate,
            'harvest_date' => $harvestDate,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-setups');

        $response->assertStatus(200);

        $setupData = collect($response->json('data.data'))->firstWhere('id', $setup->id);
        expect($setupData)->not->toBeNull()
            ->and($setupData['plant_age'])->toBe($expectedPlantAge)
            ->and($setupData['days_left'])->toBe($expectedDaysLeft)
            ->and($setupData['growth_percentage'])->toBeInt()
            ->and($setupData['growth_percentage'])->toBeGreaterThanOrEqual(0)
            ->and($setupData['growth_percentage'])->toBeLessThanOrEqual(100);
    });
});

describe('Show Hydroponic Setup', function () {
    it('user can view a specific setup', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'setup_date' => now()->subDays(15),
            'harvest_date' => now()->addDays(15),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-setups/' . $setup->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'crop_name',
                    'plant_age',
                    'days_left',
                    'growth_stage',
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $setup->id,
                ],
            ]);
    });

    it('response includes plant_age and days_left', function () {
        $setupDate = now()->subDays(20);
        $harvestDate = now()->addDays(10);
        $expectedPlantAge = (int) $setupDate->diffInDays(now());
        $expectedDaysLeft = max(0, (int) now()->diffInDays($harvestDate, false));

        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'setup_date' => $setupDate,
            'harvest_date' => $harvestDate,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-setups/' . $setup->id);

        $response->assertStatus(200);
        expect($response->json('data.plant_age'))->toBe($expectedPlantAge)
            ->and($response->json('data.days_left'))->toBe($expectedDaysLeft);
    });
});

describe('Store Hydroponic Setup', function () {
    it('user can create a new hydroponic setup', function () {
        $setupData = [
            'crop_name' => 'Lettuce',
            'number_of_crops' => 50,
            'bed_size' => 'medium',
            'pump_config' => [
                'pump_type' => 'submersible',
                'flow_rate' => 200,
            ],
            'nutrient_solution' => 'A+B Formula',
            'target_ph_min' => 5.5,
            'target_ph_max' => 6.5,
            'target_tds_min' => 800,
            'target_tds_max' => 1200,
            'water_amount' => '100 liters',
            'harvest_date' => now()->addDays(30)->toDateString(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/hydroponic-setups/store', $setupData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'crop_name',
                    'plant_age',
                    'days_left',
                ],
            ])
            ->assertJson([
                'message' => 'Hydroponic setup created successfully.',
            ]);

        $this->assertDatabaseHas('hydroponic_setup', [
            'user_id' => $this->user->id,
            'crop_name' => 'Lettuce',
            'status' => 'active',
            'harvest_status' => 'not_harvested',
            'growth_stage' => 'seedling',
        ]);
    });

    it('response includes plant_age and days_left', function () {
        $harvestDate = now()->addDays(30);
        $setupData = [
            'crop_name' => 'Tomato',
            'number_of_crops' => 30,
            'bed_size' => 'large',
            'target_ph_min' => 6.0,
            'target_ph_max' => 7.0,
            'target_tds_min' => 1000,
            'target_tds_max' => 1500,
            'water_amount' => '200 liters',
            'harvest_date' => $harvestDate->toDateString(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/hydroponic-setups/store', $setupData);

        $response->assertStatus(201);
        expect($response->json('data.plant_age'))->toBe(0) // Created today
            ->and($response->json('data.days_left'))->toBe((int) now()->diffInDays($harvestDate, false));
    });
});

describe('Mark As Harvested', function () {
    it('user can mark own setup as harvested if conditions met', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'setup_date' => now()->subDays(20), // Past day 14
            'harvest_status' => 'not_harvested',
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
            'total_count' => 100,
        ]);

        HydroponicYieldGrade::factory()->count(2)->create([
            'hydroponic_yield_id' => $yield->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/hydroponic-setups/' . $setup->id . '/mark-harvested');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'harvest_status',
                    'plant_age',
                    'days_left',
                    'yield' => [
                        'id',
                        'grades',
                    ],
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Setup marked as harvested successfully.',
                'data' => [
                    'harvest_status' => 'harvested',
                ],
            ]);

        $this->assertDatabaseHas('hydroponic_setup', [
            'id' => $setup->id,
            'harvest_status' => 'harvested',
            'status' => 'inactive',
        ]);

        expect($response->json('data.plant_age'))->toBeInt()
            ->and($response->json('data.days_left'))->toBeInt()
            ->and($response->json('data.yield'))->not->toBeNull();
    });

    it('cannot harvest if setup belongs to another user', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->otherUser->id,
            'setup_date' => now()->subDays(20),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/hydroponic-setups/' . $setup->id . '/mark-harvested');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthorized. This setup does not belong to you.',
            ]);
    });

    it('cannot harvest before day 14', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'setup_date' => now()->subDays(10), // Before day 14
            'harvest_status' => 'not_harvested',
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
            'total_count' => 100,
        ]);

        HydroponicYieldGrade::factory()->count(2)->create([
            'hydroponic_yield_id' => $yield->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/hydroponic-setups/' . $setup->id . '/mark-harvested');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Harvesting is not allowed until day 14.',
            ]);
    });

    it('cannot harvest without yield data', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'setup_date' => now()->subDays(20),
            'harvest_status' => 'not_harvested',
        ]);

        // No yield created

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/hydroponic-setups/' . $setup->id . '/mark-harvested');

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Cannot mark as harvested. Please fill in the yield data first.',
            ]);
    });

    it('cannot harvest if grades are missing', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'setup_date' => now()->subDays(20),
            'harvest_status' => 'not_harvested',
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
            'total_count' => 100,
        ]);

        // No grades created

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/hydroponic-setups/' . $setup->id . '/mark-harvested');

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Cannot mark as harvested. Yield record is missing grade breakdown.',
            ]);
    });

    it('response includes updated plant_age, days_left, and yield with grades', function () {
        $setupDate = now()->subDays(20);
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'setup_date' => $setupDate,
            'harvest_status' => 'not_harvested',
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
            'total_count' => 100,
        ]);

        $grades = HydroponicYieldGrade::factory()->count(3)->create([
            'hydroponic_yield_id' => $yield->id,
        ]);

        $expectedPlantAge = (int) $setupDate->diffInDays(now());

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/hydroponic-setups/' . $setup->id . '/mark-harvested');

        $response->assertStatus(200);
        expect($response->json('data.plant_age'))->toBe($expectedPlantAge)
            ->and($response->json('data.days_left'))->toBeInt()
            ->and($response->json('data.yield'))->not->toBeNull()
            ->and($response->json('data.yield.grades'))->toBeArray()
            ->and(count($response->json('data.yield.grades')))->toBe(3);
    });
});
