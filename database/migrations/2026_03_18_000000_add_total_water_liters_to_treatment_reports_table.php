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
        Schema::table('treatment_reports', function (Blueprint $table) {
            // Cumulative total per device; `water_liters` already exists in this table.
            $table->bigInteger('total_water_liters')->nullable()->after('water_liters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treatment_reports', function (Blueprint $table) {
            $table->dropColumn(['total_water_liters']);
        });
    }
};

