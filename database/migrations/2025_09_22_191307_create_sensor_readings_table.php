<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sensor_system_id')->index('sensor_system_id');
            $table->decimal('ph', 10, 2)->nullable();
            $table->decimal('tds', 10, 2)->nullable();
            $table->decimal('turbidity', 10, 2)->nullable();
            $table->decimal('water_level', 10, 2)->nullable();
            $table->decimal('humidity', 10, 2)->nullable();
            $table->decimal('temperature', 10, 2)->nullable();
            $table->decimal('ec', 10, 2)->nullable();
            $table->decimal('electric_current', 10, 2)->nullable();
            $table->dateTime('reading_time')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
