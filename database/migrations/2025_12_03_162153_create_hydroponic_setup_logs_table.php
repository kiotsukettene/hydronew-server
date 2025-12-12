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
        Schema::create('hydroponic_setup_logs', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('hydroponic_setup_id');
            $table->enum('growth_stage', ['seedling', 'vegetative', 'flowering', 'harvest-ready'])->nullable()->default('seedling');
            $table->decimal('ph_status', 4, 2)->nullable();
            $table->decimal('tds_status', 6, 2)->nullable();
            $table->decimal('ec_status', 6, 2)->nullable();
            $table->decimal('humidity_status', 5, 2)->nullable();
            $table->enum('health_status', ['good', 'moderate', 'poor']);
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
        Schema::dropIfExists('hydroponic_setup_logs');
    }
};
