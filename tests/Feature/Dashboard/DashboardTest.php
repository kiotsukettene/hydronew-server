<?php

use App\Models\Device;
use App\Models\HydroponicSetup;
use App\Models\SensorReading;
use App\Models\SensorSystem;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('Password123!'),
    ]);
});

describe('Dashboard Overview', function () {
    it('authenticated user can access dashboard', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $sensorSystem = SensorSystem::factory()->create([
            'device_id' => $device->id,
            'system_type' => 'clean_water',
            'is_active' => true,
        ]);

        SensorReading::factory()->create([
            'sensor_system_id' => $sensorSystem->id,
            'ph' => 6.5,
            'reading_time' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/dashboard?device_id=' . $device->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'device_id',
                'ph_levels',
                'nearest_to_harvest',
            ]);
    });

    it('returns latest pH reading data', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $sensorSystem = SensorSystem::factory()->create([
            'device_id' => $device->id,
            'system_type' => 'clean_water',
            'is_active' => true,
        ]);

        // Create multiple readings with different timestamps
        SensorReading::factory()->create([
            'sensor_system_id' => $sensorSystem->id,
            'ph' => 5.5,
            'reading_time' => now()->subHours(2),
        ]);

        $latestReading = SensorReading::factory()->create([
            'sensor_system_id' => $sensorSystem->id,
            'ph' => 7.0,
            'reading_time' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/dashboard?device_id=' . $device->id);

        $response->assertStatus(200)
            ->assertJson([
                'ph_levels' => [
                    'clean_water' => [
                        'value' => (string) $latestReading->ph,
                        'unit' => 'pH',
                        'status' => 'Good',
                    ],
                ],
            ]);
    });

    it('returns 404 when pH sensor does not exist', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        // No sensor systems created for this device

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/dashboard?device_id=' . $device->id);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'No active sensor systems found for this device.',
                'device_id' => $device->id,
            ]);
    });

    it('returns correct pH status - Good', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $sensorSystem = SensorSystem::factory()->create([
            'device_id' => $device->id,
            'system_type' => 'clean_water',
            'is_active' => true,
        ]);

        SensorReading::factory()->create([
            'sensor_system_id' => $sensorSystem->id,
            'ph' => 6.5, // Between 6.0 and 7.5
            'reading_time' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/dashboard?device_id=' . $device->id);

        $response->assertStatus(200)
            ->assertJson([
                'ph_levels' => [
                    'clean_water' => [
                        'status' => 'Good',
                    ],
                ],
            ]);
    });

    it('returns correct pH status - Acidic', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $sensorSystem = SensorSystem::factory()->create([
            'device_id' => $device->id,
            'system_type' => 'clean_water',
            'is_active' => true,
        ]);

        SensorReading::factory()->create([
            'sensor_system_id' => $sensorSystem->id,
            'ph' => 5.5, // Less than 6.0
            'reading_time' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/dashboard?device_id=' . $device->id);

        $response->assertStatus(200)
            ->assertJson([
                'ph_levels' => [
                    'clean_water' => [
                        'status' => 'Acidic',
                    ],
                ],
            ]);
    });

    it('returns correct pH status - Alkaline', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $sensorSystem = SensorSystem::factory()->create([
            'device_id' => $device->id,
            'system_type' => 'clean_water',
            'is_active' => true,
        ]);

        SensorReading::factory()->create([
            'sensor_system_id' => $sensorSystem->id,
            'ph' => 8.0, // Greater than 7.5
            'reading_time' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/dashboard?device_id=' . $device->id);

        $response->assertStatus(200)
            ->assertJson([
                'ph_levels' => [
                    'clean_water' => [
                        'status' => 'Alkaline',
                    ],
                ],
            ]);
    });

    it('returns correct pH status - Unknown', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $sensorSystem = SensorSystem::factory()->create([
            'device_id' => $device->id,
            'system_type' => 'clean_water',
            'is_active' => true,
        ]);

        // Create reading without pH value
        SensorReading::factory()->create([
            'sensor_system_id' => $sensorSystem->id,
            'ph' => null,
            'reading_time' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/dashboard?device_id=' . $device->id);

        $response->assertStatus(200)
            ->assertJson([
                'ph_levels' => [
                    'clean_water' => [
                        'value' => null,
                        'status' => 'Unknown',
                    ],
                ],
            ]);
    });

    it('returns nearest to harvest setup', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $sensorSystem = SensorSystem::factory()->create([
            'device_id' => $device->id,
            'system_type' => 'clean_water',
            'is_active' => true,
        ]);

        SensorReading::factory()->create([
            'sensor_system_id' => $sensorSystem->id,
            'ph' => 6.5,
            'reading_time' => now(),
        ]);

        // Create setups with different harvest dates
        $setup1 = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'not_harvested',
            'is_archived' => false,
            'status' => 'active',
            'harvest_date' => now()->addDays(10),
            'setup_date' => now()->subDays(20),
        ]);

        $setup2 = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'harvest_status' => 'not_harvested',
            'is_archived' => false,
            'status' => 'active',
            'harvest_date' => now()->addDays(5), // Nearest to harvest
            'setup_date' => now()->subDays(15),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/dashboard?device_id=' . $device->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'nearest_to_harvest' => [
                    'setup_id',
                    'crop_name',
                    'growth_percentage',
                ],
            ]);

        expect($response->json('nearest_to_harvest.setup_id'))->toBe($setup2->id);
    });

    it('returns null for nearest to harvest when no setup exists', function () {
        $device = Device::factory()->create(['user_id' => $this->user->id]);

        $sensorSystem = SensorSystem::factory()->create([
            'device_id' => $device->id,
            'system_type' => 'clean_water',
            'is_active' => true,
        ]);

        SensorReading::factory()->create([
            'sensor_system_id' => $sensorSystem->id,
            'ph' => 6.5,
            'reading_time' => now(),
        ]);

        // No setups created

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/dashboard?device_id=' . $device->id);

        $response->assertStatus(200)
            ->assertJson([
                'nearest_to_harvest' => null,
            ]);
    });

    it('unauthenticated user cannot access dashboard', function () {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(401);
    });
});
