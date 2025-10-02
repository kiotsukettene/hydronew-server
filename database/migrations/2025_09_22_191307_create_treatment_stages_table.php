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
        Schema::create('treatment_stages', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('treatment_id')->index('treatment_id');
            $table->enum('stage_name', ['MFC', 'Natural Filter', 'UV Filter', 'Clean Water Tank'])->default('MFC');
            $table->integer('stage_order');
            $table->enum('status', ['pending', 'processing', 'passed', 'failed'])->nullable()->default('pending');
            $table->decimal('pH', 5)->nullable();
            $table->decimal('turbidity', 10)->nullable();
            $table->decimal('TDS', 10)->nullable();
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_stages');
    }
};
