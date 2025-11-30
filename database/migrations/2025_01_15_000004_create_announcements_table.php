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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['maintenance', 'system_update', 'general', 'urgent']);
            $table->string('title');
            $table->text('message');
            $table->timestamp('start_date')->nullable(); // When to show
            $table->timestamp('end_date')->nullable(); // When to stop showing
            $table->boolean('is_active')->default(true);
            $table->enum('target_audience', ['all', 'drivers', 'passengers', 'admins'])->default('all');
            $table->timestamps();
            
            $table->index(['is_active', 'target_audience']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};

