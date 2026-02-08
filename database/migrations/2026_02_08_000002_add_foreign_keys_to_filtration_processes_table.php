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
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('treatment_report_id')->references('id')->on('treatment_reports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('filtration_processes', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropForeign(['treatment_report_id']);
        });
    }
};
