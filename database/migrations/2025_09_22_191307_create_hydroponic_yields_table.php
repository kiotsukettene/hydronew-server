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
        Schema::create('hydroponic_yields', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('hydroponic_setup_id')->index('hydroponic_setup_id');
            $table->string('plant_type', 150);
            $table->enum('growth_stage', ['seedling', 'growing', 'harvest-ready'])->nullable()->default('seedling');
            $table->enum('harvest_status', ['not_harvested', 'harvested', 'partial'])->nullable()->default('not_harvested');
            $table->integer('plant_age_days');
            $table->enum('health_status', ['good', 'moderate', 'poor']);
            $table->date('estimated_harvest_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hydroponic_yields');
    }
};
