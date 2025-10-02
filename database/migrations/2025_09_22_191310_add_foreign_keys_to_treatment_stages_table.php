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
        Schema::table('treatment_stages', function (Blueprint $table) {
            $table->foreign(['treatment_id'], 'treatment_stages_ibfk_1')->references(['id'])->on('treatment_reports')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treatment_stages', function (Blueprint $table) {
            $table->dropForeign('treatment_stages_ibfk_1');
        });
    }
};
