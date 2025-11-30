<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the ENUM to include 'accepted' status
        // MySQL doesn't support direct ENUM modification, so we use raw SQL
        DB::statement("ALTER TABLE `rides` MODIFY COLUMN `status` ENUM('requested', 'assigned', 'accepted', 'in_progress', 'completed', 'canceled') DEFAULT 'requested'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original ENUM (without 'accepted')
        // Note: This will fail if there are any rides with 'accepted' status
        // You may need to update those rides first before running this
        DB::statement("ALTER TABLE `rides` MODIFY COLUMN `status` ENUM('requested', 'assigned', 'in_progress', 'completed', 'canceled') DEFAULT 'requested'");
    }
};
