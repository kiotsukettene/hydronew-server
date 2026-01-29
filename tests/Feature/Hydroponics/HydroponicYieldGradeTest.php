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
});

describe('Yield Grade Model', function () {
    it('can create a yield grade with valid data', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
        ]);

        $grade = HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'selling',
            'count' => 50,
            'weight' => 15.5,
        ]);

        expect($grade)->not->toBeNull()
            ->and($grade->grade)->toBe('selling')
            ->and($grade->count)->toBe(50)
            ->and($grade->weight)->toBe(15.5);

        $this->assertDatabaseHas('hydroponic_yield_grades', [
            'id' => $grade->id,
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'selling',
            'count' => 50,
            'weight' => 15.5,
        ]);
    });

    it('grade field accepts only valid enum values', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
        ]);

        // Test valid grades
        $validGrades = ['selling', 'consumption', 'disposal'];
        foreach ($validGrades as $grade) {
            $yieldGrade = HydroponicYieldGrade::create([
                'hydroponic_yield_id' => $yield->id,
                'grade' => $grade,
                'count' => 10,
            ]);

            expect($yieldGrade->grade)->toBe($grade);
        }
    });

    it('weight can be nullable', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
        ]);

        $grade = HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'consumption',
            'count' => 30,
            'weight' => null,
        ]);

        expect($grade->weight)->toBeNull()
            ->and($grade->count)->toBe(30);
    });

    it('count defaults to 0 when not provided', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
        ]);

        // Create grade with explicit count = 0
        $grade = HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'disposal',
            'count' => 0,
        ]);

        expect($grade->count)->toBe(0);
        
        $this->assertDatabaseHas('hydroponic_yield_grades', [
            'id' => $grade->id,
            'count' => 0,
        ]);
    });
});

describe('Yield Grade Relationships', function () {
    it('belongs to hydroponic yield', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
        ]);

        $grade = HydroponicYieldGrade::factory()->create([
            'hydroponic_yield_id' => $yield->id,
        ]);

        expect($grade->hydroponic_yield)->not->toBeNull()
            ->and($grade->hydroponic_yield->id)->toBe($yield->id)
            ->and($grade->hydroponic_yield_id)->toBe($yield->id);
    });

    it('yield can have multiple grades', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
        ]);

        HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'selling',
            'count' => 50,
            'weight' => 15.0,
        ]);

        HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'consumption',
            'count' => 30,
            'weight' => 10.0,
        ]);

        HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'disposal',
            'count' => 20,
            'weight' => 5.0,
        ]);

        $grades = HydroponicYieldGrade::where('hydroponic_yield_id', $yield->id)->get();

        expect($grades->count())->toBe(3)
            ->and($grades->where('grade', 'selling')->first()->count)->toBe(50)
            ->and($grades->where('grade', 'consumption')->first()->count)->toBe(30)
            ->and($grades->where('grade', 'disposal')->first()->count)->toBe(20);
    });
});

describe('Yield Grade Data Integrity', function () {
    it('calculates total count across all grades correctly', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
            'number_of_crops' => 100,
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
            'total_count' => 80,
        ]);

        HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'selling',
            'count' => 50,
        ]);

        HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'consumption',
            'count' => 30,
        ]);

        $totalGradeCount = HydroponicYieldGrade::where('hydroponic_yield_id', $yield->id)
            ->sum('count');

        expect($totalGradeCount)->toBe(80)
            ->and($totalGradeCount)->toBe($yield->total_count);
    });

    it('calculates total weight across all grades correctly', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
            'total_weight' => 25.5,
        ]);

        HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'selling',
            'count' => 50,
            'weight' => 15.0,
        ]);

        HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'consumption',
            'count' => 30,
            'weight' => 10.5,
        ]);

        $totalGradeWeight = HydroponicYieldGrade::where('hydroponic_yield_id', $yield->id)
            ->sum('weight');

        expect($totalGradeWeight)->toBe(25.5)
            ->and($totalGradeWeight)->toBe($yield->total_weight);
    });

    it('disposal grade represents unharvested crops', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
            'number_of_crops' => 100,
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
            'total_count' => 75, // 75 harvested out of 100
        ]);

        HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'selling',
            'count' => 50,
        ]);

        HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'consumption',
            'count' => 25,
        ]);

        // Disposal grade should be created to represent the difference
        $expectedDisposal = $setup->number_of_crops - $yield->total_count;
        
        HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'disposal',
            'count' => $expectedDisposal,
        ]);

        $disposalGrade = HydroponicYieldGrade::where('hydroponic_yield_id', $yield->id)
            ->where('grade', 'disposal')
            ->first();

        expect($disposalGrade->count)->toBe(25)
            ->and($setup->number_of_crops)->toBe($yield->total_count + $disposalGrade->count);
    });
});

describe('Yield Grade Updates', function () {
    it('can update grade count and weight', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
        ]);

        $grade = HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'selling',
            'count' => 50,
            'weight' => 15.0,
        ]);

        $grade->update([
            'count' => 60,
            'weight' => 18.0,
        ]);

        $grade->refresh();

        expect($grade->count)->toBe(60)
            ->and($grade->weight)->toBe(18.0);

        $this->assertDatabaseHas('hydroponic_yield_grades', [
            'id' => $grade->id,
            'count' => 60,
            'weight' => 18.0,
        ]);
    });

    it('can delete a grade', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
        ]);

        $grade = HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'selling',
            'count' => 50,
        ]);

        $gradeId = $grade->id;
        $grade->delete();

        $this->assertDatabaseMissing('hydroponic_yield_grades', [
            'id' => $gradeId,
        ]);

        expect(HydroponicYieldGrade::find($gradeId))->toBeNull();
    });
});

describe('Yield Grade Statistics', function () {
    it('can calculate statistics for a specific grade type', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        // Create multiple yields with selling grades
        for ($i = 0; $i < 3; $i++) {
            $yield = HydroponicYield::factory()->create([
                'hydroponic_setup_id' => $setup->id,
            ]);

            HydroponicYieldGrade::create([
                'hydroponic_yield_id' => $yield->id,
                'grade' => 'selling',
                'count' => 50 + ($i * 10),
                'weight' => 15.0 + ($i * 5.0),
            ]);
        }

        $totalSellingCount = HydroponicYieldGrade::where('grade', 'selling')->sum('count');
        $totalSellingWeight = HydroponicYieldGrade::where('grade', 'selling')->sum('weight');

        expect($totalSellingCount)->toBe(180) // 50 + 60 + 70
            ->and((float) $totalSellingWeight)->toBe(60.0); // 15 + 20 + 25
    });

    it('can get grades grouped by type', function () {
        $setup = HydroponicSetup::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $yield = HydroponicYield::factory()->create([
            'hydroponic_setup_id' => $setup->id,
        ]);

        HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'selling',
            'count' => 50,
        ]);

        HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'consumption',
            'count' => 30,
        ]);

        HydroponicYieldGrade::create([
            'hydroponic_yield_id' => $yield->id,
            'grade' => 'disposal',
            'count' => 20,
        ]);

        $gradesByType = HydroponicYieldGrade::where('hydroponic_yield_id', $yield->id)
            ->get()
            ->groupBy('grade');

        expect($gradesByType->keys()->toArray())->toContain('selling', 'consumption', 'disposal')
            ->and($gradesByType['selling']->first()->count)->toBe(50)
            ->and($gradesByType['consumption']->first()->count)->toBe(30)
            ->and($gradesByType['disposal']->first()->count)->toBe(20);
    });
});

describe('Yield Grade Factory', function () {
    it('factory creates valid yield grade', function () {
        $grade = HydroponicYieldGrade::factory()->create();

        expect($grade)->not->toBeNull()
            ->and($grade->hydroponic_yield_id)->toBeInt()
            ->and($grade->grade)->toBeString()
            ->and($grade->count)->toBeInt()
            ->and($grade->count)->toBeGreaterThanOrEqual(0);
    });

    it('factory can create multiple grades', function () {
        $grades = HydroponicYieldGrade::factory()->count(5)->create();

        expect($grades->count())->toBe(5);

        foreach ($grades as $grade) {
            expect($grade->hydroponic_yield_id)->toBeInt()
                ->and($grade->count)->toBeInt();
        }
    });
});

