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
        Schema::table('tips_suggestions', function (Blueprint $table) {
            // Change category from enum to string to allow any category value
            $table->string('category', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tips_suggestions', function (Blueprint $table) {
            // Revert back to enum
            $table->enum('category', ['Water Quality', 'Plant Growth', 'System Maintenance'])->change();
        });
    }
};
