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
        Schema::create('hydroponic_pump_states', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('device_id')->unique();
            $table->boolean('pump_2_state')->default(false);
            $table->decimal('pump_2_target_liters', 8, 2)->nullable();
            $table->dateTime('pump_2_started_at')->nullable();
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hydroponic_pump_states');
    }
};
