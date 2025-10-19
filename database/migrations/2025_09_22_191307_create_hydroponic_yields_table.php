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
            $table->enum('harvest_status', ['not_harvested', 'harvested', 'partial'])->nullable()->default('not_harvested');
            $table->enum('growth_stage', ['seedling', 'vegetative', 'flowering', 'harvest-ready'])->nullable()->default('seedling');
            $table->enum('health_status', ['good', 'moderate', 'poor']);
            $table->decimal('predicted_yield', 10, 2)->nullable();
            $table->decimal('actual_yield', 10, 2)->nullable();
            $table->dateTime('harvest_date')->nullable();
            $table->boolean('system_generated')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
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
