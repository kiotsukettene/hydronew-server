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
        Schema::create('sensor_systems', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('device_id')->index();
            $table->enum('system_type', ['dirty_water', 'clean_water', 'hydroponics_water']);
            $table->string('name')->nullable(); // Optional friendly name
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Ensure one system type per device
            $table->unique(['device_id', 'system_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_systems');
    }
};
