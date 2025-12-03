<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hydroponic_yields', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('hydroponic_setup_id');
            // Actual harvest results
            $table->decimal('total_weight', 10, 2)->nullable(); // grams or kg
            $table->integer('total_count')->nullable(); // e.g., number of heads
            $table->enum('quality_grade', ['selling', 'consumption', 'disposal'])->nullable();

            $table->date('harvest_date');
            $table->text('notes')->nullable();

            // for archive feature
            $table->boolean('is_archived')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hydroponic_yields');
    }
};
