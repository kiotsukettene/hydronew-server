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
        Schema::create('treatment_reports', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('device_id');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->enum('final_status', ['pending', 'success', 'failed'])->nullable()->default('pending');
            $table->integer('total_cycles')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_reports');
    }
};
