<?php

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
});

describe('List Harvested Yields', function () {
    it('authenticated user can view harvested yields', function () {
        // Create harvested setups with yields
        $setup1 = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'harvested',
            'setup_date' => now()->subDays(30),
            'harvest_date' => now()->subDays(5),
        ]);

        $yield1 = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup1->id,
        ]);

        HydroponicYieldGrade::factory()->create([
            'hydroponic_yield_id' => $yield1->id,
            'grade' => 'selling',
            'count' => 50,
        ]);

        // Create non-harvested setup (should not appear)
        $setup2 = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'not_harvested',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-yields');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'statistics' => [
                    'total_harvested_setups',
                    'total_sold',
                    'total_consumed',
                    'total_disposed',
                ],
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

        expect($response->json('data.total'))->toBe(1);
    });

    it('filtering returns expected results', function () {
        // Create harvested setups with different crop names
        $setup1 = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'harvested',
            'crop_name' => 'Lettuce',
            'harvest_date' => now()->subDays(5),
        ]);

        $setup2 = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'harvested',
            'crop_name' => 'Tomato',
            'harvest_date' => now()->subDays(5),
        ]);

        HydroponicYield::factory()->create(['hydroponic_setup_id' => $setup1->id]);
        HydroponicYield::factory()->create(['hydroponic_setup_id' => $setup2->id]);

        // Search filter
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-yields?search=Lettuce');

        $response->assertStatus(200);
        expect($response->json('data.total'))->toBe(1)
            ->and($response->json('data.data.0.crop_name'))->toBe('Lettuce');
    });

    it('pagination works correctly', function () {
        // Create 15 harvested setups
        $setups = HydroponicSetup::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'harvested',
            'harvest_date' => now()->subDays(5),
        ]);

        foreach ($setups as $setup) {
            HydroponicYield::factory()->create(['hydroponic_setup_id' => $setup->id]);
        }

        // First page (default 10 per page)
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-yields');

        $response->assertStatus(200);
        expect($response->json('data.per_page'))->toBe(10)
            ->and($response->json('data.total'))->toBe(15)
            ->and($response->json('data.current_page'))->toBe(1)
            ->and(count($response->json('data.data')))->toBe(10);

        // Second page
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-yields?page=2');

        $response->assertStatus(200);
        expect($response->json('data.current_page'))->toBe(2)
            ->and(count($response->json('data.data')))->toBe(5);
    });

    it('statistics returned match actual yields', function () {
        // Create harvested setups with different grades
        $setup1 = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'harvested',
            'harvest_date' => now()->subDays(5),
        ]);

        $yield1 = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup1->id,
        ]);

        HydroponicYieldGrade::factory()->create([
            'hydroponic_yield_id' => $yield1->id,
            'grade' => 'selling',
            'count' => 30,
        ]);

        HydroponicYieldGrade::factory()->create([
            'hydroponic_yield_id' => $yield1->id,
            'grade' => 'consumption',
            'count' => 20,
        ]);

        HydroponicYieldGrade::factory()->create([
            'hydroponic_yield_id' => $yield1->id,
            'grade' => 'disposal',
            'count' => 10,
        ]);

        $setup2 = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'harvested',
            'harvest_date' => now()->subDays(5),
        ]);

        $yield2 = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup2->id,
        ]);

        HydroponicYieldGrade::factory()->create([
            'hydroponic_yield_id' => $yield2->id,
            'grade' => 'selling',
            'count' => 50,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-yields');

        $response->assertStatus(200);
        expect($response->json('statistics.total_harvested_setups'))->toBe(2)
            ->and($response->json('statistics.total_sold'))->toBe(80) // 30 + 50
            ->and($response->json('statistics.total_consumed'))->toBe(20)
            ->and($response->json('statistics.total_disposed'))->toBe(10);
    });

    it('duration_days is calculated correctly', function () {
        $setupDate = now()->subDays(30);
        $harvestDate = now()->subDays(5);
        $expectedDuration = (int) $setupDate->diffInDays($harvestDate, false);

        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'harvested',
            'setup_date' => $setupDate,
            'harvest_date' => $harvestDate,
        ]);

        HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-yields');

        $response->assertStatus(200);
        $setupData = collect($response->json('data.data'))->firstWhere('id', $setup->id);
        expect($setupData)->not->toBeNull()
            ->and($setupData['duration_days'])->toBe($expectedDuration);
    });
});

describe('Show Yield for Setup', function () {
    it('user can view yield data for a specific setup', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'setup_date' => now()->subDays(20),
            'harvest_date' => now()->addDays(10),
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-yields/' . $setup->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'crop_name',
                        'plant_age',
                        'days_left',
                        'growth_stage',
                        'health_status',
                        'harvest_status',
                    ],
                ],
            ])
            ->assertJson([
                'status' => 'success',
            ]);
    });

    it('response includes plant_age, days_left, growth_stage, health_status, harvest_status', function () {
        $setupDate = now()->subDays(15);
        $expectedPlantAge = (int) $setupDate->diffInDays(now());

        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'setup_date' => $setupDate,
            'harvest_date' => now()->addDays(15),
            'growth_stage' => 'vegetative',
            'health_status' => 'good',
            'harvest_status' => 'not_harvested',
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/hydroponic-yields/' . $setup->id);

        $response->assertStatus(200);
        $yieldData = $response->json('data.0');
        expect($yieldData['plant_age'])->toBe($expectedPlantAge)
            ->and($yieldData['days_left'])->toBeNull() // Yields don't have harvest_date, so days_left is null
            ->and($yieldData['growth_stage'])->toBe('vegetative')
            ->and($yieldData['health_status'])->toBe('good')
            ->and($yieldData['harvest_status'])->toBe('not_harvested');
    });
});

describe('Store Yield', function () {
    it('user can store new yield for a setup', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'number_of_crops' => 100,
        ]);

        $yieldData = [
            'total_count' => 80,
            'total_weight' => 25.5,
            'notes' => 'First harvest',
            'grades' => [
                [
                    'grade' => 'selling',
                    'count' => 50,
                    'weight' => 15.0,
                ],
                [
                    'grade' => 'consumption',
                    'count' => 30,
                    'weight' => 10.5,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/hydroponic-yields/' . $setup->id . '/store', $yieldData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'yield' => [
                        'id',
                        'total_count',
                        'total_weight',
                        'grades',
                    ],
                    'summary' => [
                        'total_crops_in_setup',
                        'total_harvested',
                        'total_disposed',
                    ],
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Yield data stored successfully.',
                'data' => [
                    'summary' => [
                        'total_crops_in_setup' => 100,
                        'total_harvested' => 80,
                        'total_disposed' => 20, // 100 - 80
                    ],
                ],
            ]);

        $this->assertDatabaseHas('hydroponic_yields', [
            'hydroponic_setup_id' => $setup->id,
            'total_count' => 80,
            'total_weight' => 25.5,
        ]);
    });

    it('user can update existing yield for a setup', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'number_of_crops' => 100,
        ]);

        $existingYield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
            'total_count' => 70,
        ]);

        $yieldData = [
            'total_count' => 85,
            'total_weight' => 30.0,
            'notes' => 'Updated harvest',
            'grades' => [
                [
                    'grade' => 'selling',
                    'count' => 60,
                    'weight' => 20.0,
                ],
                [
                    'grade' => 'consumption',
                    'count' => 25,
                    'weight' => 10.0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/hydroponic-yields/' . $setup->id . '/store', $yieldData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Yield data updated successfully.',
            ]);

        $this->assertDatabaseHas('hydroponic_yields', [
            'id' => $existingYield->id,
            'total_count' => 85,
            'total_weight' => 30.0,
        ]);
    });

    it('grade records are created correctly, including disposal grade', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'number_of_crops' => 100,
        ]);

        $yieldData = [
            'total_count' => 75,
            'grades' => [
                [
                    'grade' => 'selling',
                    'count' => 50,
                ],
                [
                    'grade' => 'consumption',
                    'count' => 25,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/hydroponic-yields/' . $setup->id . '/store', $yieldData);

        $response->assertStatus(201);

        $yield = HydroponicYield::where('hydroponic_setup_id', $setup->id)->first();
        expect($yield)->not->toBeNull();

        $grades = HydroponicYieldGrade::where('hydroponic_yield_id', $yield->id)->get();
        expect($grades->count())->toBe(3) // 2 user grades + 1 disposal grade
            ->and($grades->where('grade', 'selling')->first()->count)->toBe(50)
            ->and($grades->where('grade', 'consumption')->first()->count)->toBe(25)
            ->and($grades->where('grade', 'disposal')->first()->count)->toBe(25); // 100 - 75
    });

    it('response includes yield data and summary', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'number_of_crops' => 100,
        ]);

        $yieldData = [
            'total_count' => 80,
            'total_weight' => 25.5,
            'notes' => 'Test harvest',
            'grades' => [
                [
                    'grade' => 'selling',
                    'count' => 50,
                    'weight' => 15.0,
                ],
                [
                    'grade' => 'consumption',
                    'count' => 30,
                    'weight' => 10.5,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/hydroponic-yields/' . $setup->id . '/store', $yieldData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'yield' => [
                        'id',
                        'total_count',
                        'total_weight',
                        'notes',
                        'grades',
                    ],
                    'summary' => [
                        'total_crops_in_setup',
                        'total_harvested',
                        'total_disposed',
                    ],
                ],
            ]);

        expect($response->json('data.yield.total_count'))->toBe(80)
            ->and($response->json('data.yield.grades'))->toBeArray()
            ->and(count($response->json('data.yield.grades')))->toBe(3) // 2 user grades + disposal
            ->and($response->json('data.summary.total_crops_in_setup'))->toBe(100)
            ->and($response->json('data.summary.total_harvested'))->toBe(80)
            ->and($response->json('data.summary.total_disposed'))->toBe(20);
    });
});
