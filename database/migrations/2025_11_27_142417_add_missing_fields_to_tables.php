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
        // Add phone to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
        });

        // Add notes to rides table
        Schema::table('rides', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('drop_address');
        });

        // Add location_updated_at to driver_profiles table
        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->timestamp('location_updated_at')->nullable()->after('current_lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });

        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->dropColumn('location_updated_at');
        });
    }
};
