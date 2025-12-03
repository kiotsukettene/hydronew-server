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
        Schema::create('hydroponic_setup', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('user_id');

            // Crop and setup configuration
            $table->string('crop_name', 100);
            $table->integer('number_of_crops')->default(0);
            $table->enum('bed_size', ['small', 'medium', 'large']);
            $table->json('pump_config')->nullable();
            $table->string('nutrient_solution', 255)->nullable();

            // Ideal parameter ranges
            $table->decimal('target_ph_min', 4, 2);
            $table->decimal('target_ph_max', 4, 2);
            $table->decimal('target_tds_min', 6, 2);
            $table->decimal('target_tds_max', 6, 2);

            // Other details
            $table->enum('harvest_status', ['not_harvested', 'harvested', 'partial'])->nullable()->default('not_harvested');
            $table->string('water_amount', 50)->nullable();
            $table->dateTime('setup_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->nullable()->default('active');
            $table->boolean('is_archived')->default(false);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hydroponic_setup');
    }
};
