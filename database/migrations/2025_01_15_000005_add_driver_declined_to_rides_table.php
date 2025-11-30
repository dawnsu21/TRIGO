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
            $table->timestamp('driver_declined_at')->nullable()->after('canceled_at');
            $table->foreignId('declined_by_driver_id')->nullable()->after('driver_declined_at')
                ->constrained('users')->nullOnDelete();
            $table->text('decline_reason')->nullable()->after('declined_by_driver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropForeign(['declined_by_driver_id']);
            $table->dropColumn(['driver_declined_at', 'declined_by_driver_id', 'decline_reason']);
        });
    }
};

