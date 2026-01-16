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
        Schema::create('devices', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('device_name', 150);
            $table->string('serial_number', 150)->unique('serial_number');
            $table->string('model', 100)->nullable();
            $table->string('firmware_version', 50)->nullable();
            $table->enum('status', ['online', 'offline'])->nullable()->default('offline');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};