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
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->foreign(['sensor_system_id'], 'sensor_readings_ibfk_1')->references(['id'])->on('sensor_systems')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->dropForeign('sensor_readings_ibfk_1');
        });
    }
};
