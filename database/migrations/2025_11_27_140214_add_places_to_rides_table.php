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
        Schema::table('rides', function (Blueprint $table) {
            // Add place foreign keys
            $table->foreignId('pickup_place_id')->nullable()->after('driver_id')->constrained('places')->nullOnDelete();
            $table->foreignId('dropoff_place_id')->nullable()->after('pickup_place_id')->constrained('places')->nullOnDelete();
            
            // Keep old coordinate fields for backward compatibility (can be removed later)
            // They will be populated from places if place_id is set
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropForeign(['pickup_place_id']);
            $table->dropForeign(['dropoff_place_id']);
            $table->dropColumn(['pickup_place_id', 'dropoff_place_id']);
        });
    }
};
