<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Paused = treatment paused due to device offline (valve 1 was open, water in anode).
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE filtration_processes MODIFY COLUMN status ENUM('active','paused','completed','failed','restarting') DEFAULT 'active'");
        }
        // SQLite and others: enum is stored as string, so 'paused' is already allowed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE filtration_processes MODIFY COLUMN status ENUM('active','completed','failed','restarting') DEFAULT 'active'");
        }
    }
};
