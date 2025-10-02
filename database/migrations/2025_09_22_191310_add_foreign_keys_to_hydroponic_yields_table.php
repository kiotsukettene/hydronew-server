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
        Schema::table('hydroponic_yields', function (Blueprint $table) {
            $table->foreign(['hydroponic_setup_id'], 'hydroponic_yields_ibfk_1')->references(['id'])->on('hydroponic_setup')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hydroponic_yields', function (Blueprint $table) {
            $table->dropForeign('hydroponic_yields_ibfk_1');
        });
    }
};
