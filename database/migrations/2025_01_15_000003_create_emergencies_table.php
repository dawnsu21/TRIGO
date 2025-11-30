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
        Schema::create('emergencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Who reported
            $table->foreignId('ride_id')->nullable()->constrained('rides')->nullOnDelete(); // Related ride if any
            $table->enum('reported_by_role', ['passenger', 'driver']);
            $table->enum('type', ['safety_concern', 'driver_emergency', 'passenger_emergency', 'accident', 'other']);
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['pending', 'acknowledged', 'resolved', 'dismissed'])->default('pending');
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete(); // Admin who acknowledged
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergencies');
    }
};

