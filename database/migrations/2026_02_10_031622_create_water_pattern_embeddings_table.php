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
        Schema::create('water_pattern_embeddings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('device_id')->index();
            $table->enum('system_type', ['dirty_water', 'clean_water', 'hydroponics_water'])->index();
            $table->text('pattern_text');
            $table->json('embedding');
            $table->string('embedding_model', 100);
            $table->unsignedInteger('embedding_dim');
            $table->dateTime('period_start')->index();
            $table->dateTime('period_end')->index();
            $table->json('metadata');
            $table->timestamps();

            // Foreign key
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_pattern_embeddings');
    }
};
