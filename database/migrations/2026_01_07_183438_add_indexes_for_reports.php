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
        // Optimize sensor_readings queries for historical data
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->index(['sensor_system_id', 'reading_time'], 'idx_sensor_readings_system_time');
        });

        // Optimize hydroponic_setup_logs queries for growth tracking
        Schema::table('hydroponic_setup_logs', function (Blueprint $table) {
            $table->index(['hydroponic_setup_id', 'created_at'], 'idx_setup_logs_setup_time');
        });

        // Optimize treatment_reports queries for performance analytics
        Schema::table('treatment_reports', function (Blueprint $table) {
            $table->index(['device_id', 'start_time'], 'idx_treatment_reports_device_time');
            $table->index(['final_status', 'start_time'], 'idx_treatment_reports_status_time');
        });

        // Optimize treatment_stages queries for stage-by-stage analysis
        Schema::table('treatment_stages', function (Blueprint $table) {
            $table->index(['treatment_id', 'stage_order'], 'idx_treatment_stages_treatment_order');
        });

        // Optimize hydroponic_setup queries for crop analytics
        Schema::table('hydroponic_setup', function (Blueprint $table) {
            $table->index(['user_id', 'harvest_status', 'is_archived'], 'idx_hydroponic_setup_user_status');
            $table->index(['crop_name', 'harvest_date'], 'idx_hydroponic_setup_crop_harvest');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->dropIndex('idx_sensor_readings_system_time');
        });

        Schema::table('hydroponic_setup_logs', function (Blueprint $table) {
            $table->dropIndex('idx_setup_logs_setup_time');
        });

        Schema::table('treatment_reports', function (Blueprint $table) {
            $table->dropIndex('idx_treatment_reports_device_time');
            $table->dropIndex('idx_treatment_reports_status_time');
        });

        Schema::table('treatment_stages', function (Blueprint $table) {
            $table->dropIndex('idx_treatment_stages_treatment_order');
        });

        Schema::table('hydroponic_setup', function (Blueprint $table) {
            $table->dropIndex('idx_hydroponic_setup_user_status');
            $table->dropIndex('idx_hydroponic_setup_crop_harvest');
        });
    }
};
