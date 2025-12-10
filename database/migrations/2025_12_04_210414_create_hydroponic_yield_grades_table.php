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
        Schema::create('hydroponic_yield_grades', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('hydroponic_yield_id');
            $table->enum('grade', ['selling', 'consumption', 'disposal']);
            $table->integer('count')->default(0);
            $table->decimal('weight', 10, 2)->nullable(); // optional per grade weight
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hydroponic_yield_grades');
    }
};
