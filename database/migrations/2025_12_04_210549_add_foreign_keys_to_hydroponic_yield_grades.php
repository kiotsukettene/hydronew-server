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
        Schema::table('hydroponic_yield_grades', function (Blueprint $table) {
            $table->foreign(['hydroponic_yield_id'], 'hydroponic_yield_grades_ibfk_1')->references(['id'])->on('hydroponic_yields')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hydroponic_yield_grades', function (Blueprint $table) {
            //
        });
    }
};
