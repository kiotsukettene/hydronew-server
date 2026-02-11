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
        Schema::create('filtration_processes', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('device_id');
            $table->bigInteger('treatment_report_id')->index('treatment_report_id');
            $table->enum('status', ['active', 'completed', 'failed', 'restarting'])->default('active');
            $table->boolean('pump_3_state')->default(false);
            $table->boolean('valve_1_state')->default(false);
            $table->boolean('valve_2_state')->default(false);
            $table->dateTime('stage_1_started_at')->nullable();
            $table->dateTime('stages_2_4_started_at')->nullable();
            $table->integer('restart_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filtration_processes');
    }
};
