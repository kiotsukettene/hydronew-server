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
        Schema::create('tips_suggestions', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('title');
            $table->text('description');
            $table->enum('category', ['Water Quality', 'Plant Growth', 'System Maintenance']);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tips_suggestions');
    }
};
