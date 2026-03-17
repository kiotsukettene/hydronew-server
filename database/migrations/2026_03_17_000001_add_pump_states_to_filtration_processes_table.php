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
        Schema::table('filtration_processes', function (Blueprint $table) {
            $table->boolean('pump_2_state')->default(false)->after('valve_2_state');
            $table->boolean('pump_4_state')->default(false)->after('pump_2_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('filtration_processes', function (Blueprint $table) {
            $table->dropColumn(['pump_2_state', 'pump_4_state']);
        });
    }
};
