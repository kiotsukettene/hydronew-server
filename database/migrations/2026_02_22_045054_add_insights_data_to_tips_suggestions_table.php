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
            $table->string('device_id')->nullable()->after('id');
            $table->enum('system_type', ['dirty_water', 'clean_water', 'hydroponics_water'])->default('clean_water')->after('device_id');
            $table->json('insights')->nullable()->after('category');
            $table->json('current_reading')->nullable()->after('insights');
            $table->json('statuses')->nullable()->after('current_reading');
            $table->json('missing_sensors')->nullable()->after('statuses');
            $table->json('evidence')->nullable()->after('missing_sensors');
            $table->json('retrieved_context')->nullable()->after('evidence');
            $table->timestamp('updated_at')->nullable()->after('created_at');
            $table->timestamp('expires_at')->nullable()->after('updated_at');
            
            // Add index for faster cache lookups
            $table->index(['device_id', 'system_type', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tips_suggestions', function (Blueprint $table) {
            $table->dropIndex(['device_id', 'system_type', 'expires_at']);
            $table->dropColumn([
                'device_id',
                'system_type',
                'insights',
                'current_reading',
                'statuses',
                'missing_sensors',
                'evidence',
                'retrieved_context',
                'updated_at',
                'expires_at'
            ]);
        });
    }
};
